<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Warehouse;
use App\Sync\WarehouseStockClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private const SORTABLE = ['sku', 'name', 'category', 'warehouse', 'stock', 'status', 'updated'];

    /** Berapa lama hasil API di-cache agar sort/paginate tidak menembak API lagi. */
    private const CACHE_SECONDS = 120;

    public function index(Request $request): View
    {
        // `partial` hanya menentukan view; jangan biarkan ikut ke URL yang
        // dibangun link sort/paginasi (fullUrlWithQuery/withQueryString).
        $isPartial = $request->boolean('partial');
        $request->query->remove('partial');

        $data = $this->prepare($request);
        $sorted = $data['sorted'];

        // ---- Paginasi di memori ----
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $sorted->forPage($page, $data['perPage'])->values(),
            $sorted->count(),
            $data['perPage'],
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Permintaan AJAX (filter/sort/paginasi) hanya butuh isi yang di-swap.
        $view = $isPartial ? 'inventory._app' : 'inventory.index';

        return view($view, [
            'items' => $items,
        ] + $data);
    }

    /**
     * Ekspor hasil (dengan filter aktif) ke berkas Excel yang rapi.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->prepare($request);

        return (new \App\Exports\InventoryExport($data))->download();
    }

    /**
     * Bangun seluruh konteks + baris terurut (belum dipaginasi) dari API,
     * dipakai bersama oleh index() dan export().
     *
     * @return array<string, mixed>
     */
    private function prepare(Request $request): array
    {
        $divisions = Division::orderBy('name')->get();

        // Data inventory diambil LANGSUNG dari API gudang, di-scope per divisi.
        // Filter divisi selalu tunggal agar volume data dari API terkendali.
        $division = $divisions->firstWhere('id', (int) $request->query('division')) ?? $divisions->first();

        // Gudang aktif pada divisi terpilih (untuk dropdown + target pemanggilan API).
        $divisionWarehouses = $division
            ? Warehouse::where('division_id', $division->id)->where('is_active', true)
                ->orderBy('sequence')->orderBy('name')->get()
            : collect();

        // Gudang terpilih harus milik divisi ini; kosong = semua gudang divisi.
        $selectedWarehouse = $divisionWarehouses->firstWhere('id', (int) $request->query('warehouse'));
        $targets = $selectedWarehouse ? collect([$selectedWarehouse]) : $divisionWarehouses;

        // Filter tanggal (opsional) diteruskan ke API sebagai `as_of` = POSISI STOK
        // pada tanggal itu (snapshot historis). Kosong = stok terkini.
        [$asOfRaw, $asOf] = $this->resolveAsOf($request);

        // Tarik data live dari tiap gudang target.
        $rows = collect();
        $errors = [];
        $fetchedAt = null;
        $fresh = $request->boolean('fresh');

        foreach ($targets as $warehouse) {
            if (blank($warehouse->base_url)) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): belum dikonfigurasi — Base URL kosong.";
                continue;
            }

            try {
                [$items, $time] = $this->fetchWarehouse($warehouse, $fresh, $asOf);
                $fetchedAt = $fetchedAt === null ? $time : max($fetchedAt, $time);
                foreach ($items as $item) {
                    $rows->push($item);
                }
            } catch (\Throwable $e) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): {$e->getMessage()}";
            }
        }

        // Mode "semua gudang" → gabungkan jadi satu baris per SKU dengan stok
        // per gudang + total. Satu gudang dipilih → tetap satu baris per SKU
        // gudang itu (tampilan lama).
        $grouped = $selectedWarehouse === null;
        $dataset = $grouped ? $this->groupBySku($rows) : $rows;

        // ---- Filter di memori ----
        $search   = trim((string) $request->query('search', ''));
        $category = $request->query('category');
        $status   = $request->query('status');

        $filtered = $dataset
            ->when($search !== '', fn (Collection $c) => $c->filter(
                fn ($r) => str_contains(mb_strtolower($r['sku'] . ' ' . $r['name'] . ' ' . (string) $r['category']), mb_strtolower($search))
            ))
            ->when($category, fn (Collection $c) => $c->where('category', $category))
            ->when(in_array($status, ['tersedia', 'menipis', 'habis'], true), fn (Collection $c) => $c->where('status_key', $status))
            ->values();

        $summary = [
            'items' => $filtered->count(),
            'stock' => (float) $filtered->sum('qty'),
            'low'   => $filtered->where('status_key', 'menipis')->count(),
            'out'   => $filtered->where('status_key', 'habis')->count(),
        ];

        // ---- Urutkan di memori ----
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'sku';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $sortKey = match ($sort) {
            'stock'     => 'qty',
            'status'    => 'status_order',
            'warehouse' => 'warehouse_name',
            default     => $sort,
        };
        $sorted = ($direction === 'desc'
            ? $filtered->sortByDesc($sortKey, SORT_NATURAL | SORT_FLAG_CASE)
            : $filtered->sortBy($sortKey, SORT_NATURAL | SORT_FLAG_CASE)
        )->values();

        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 25;
        $categories = $rows->pluck('category')->filter()->unique()->sort()->values();

        return compact(
            'divisions', 'division', 'divisionWarehouses', 'selectedWarehouse', 'targets',
            'sorted', 'summary', 'categories', 'sort', 'direction', 'perPage',
            'errors', 'fetchedAt', 'asOfRaw', 'grouped'
        );
    }

    /**
     * Gabungkan baris per-(SKU × gudang) menjadi satu baris per SKU.
     * Menyimpan stok tiap gudang di `per_wh` (code => [qty, min, status_key]),
     * total stok di `qty`, dan status agregat dari total vs jumlah min.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function groupBySku(Collection $rows): Collection
    {
        return $rows->groupBy('sku')->map(function (Collection $group) {
            $first = $group->first();

            $perWh = [];
            foreach ($group as $r) {
                $perWh[$r['warehouse_code']] = [
                    'qty'        => $r['qty'],
                    'min'        => $r['min_qty'],
                    'status_key' => $r['status_key'],
                ];
            }

            $total    = (float) $group->sum('qty');
            $totalMin = (float) $group->sum('min_qty');
            $statusKey = $total <= 0 ? 'habis' : ($total <= $totalMin ? 'menipis' : 'tersedia');

            return [
                'sku'          => $first['sku'],
                'name'         => $first['name'],
                'category'     => $first['category'],
                'uom'          => $first['uom'],
                'qty'          => $total,
                'min_qty'      => $totalMin,
                'per_wh'       => $perWh,
                'status_key'   => $statusKey,
                'status_order' => $statusKey === 'habis' ? 0 : ($statusKey === 'menipis' ? 1 : 2),
                'updated'      => $group->max('updated'),
            ];
        })->values();
    }

    /**
     * Baca query `as_of` (Y-m-d). Mengembalikan [raw untuk view, CarbonImmutable
     * untuk API]. Tanggal masa depan dipangkas ke hari ini (snapshot tidak bisa
     * melampaui sekarang). Kosong/invalid = null (stok terkini).
     *
     * @return array{0: ?string, 1: ?CarbonImmutable}
     */
    private function resolveAsOf(Request $request): array
    {
        $value = $request->query('as_of');
        if (blank($value)) {
            return [null, null];
        }

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value, config('app.timezone'));
        } catch (\Throwable) {
            $date = false;
        }

        if (! $date) {
            return [null, null];
        }

        $today = CarbonImmutable::now(config('app.timezone'));
        if ($date->greaterThan($today)) {
            $date = $today;
        }

        return [$date->format('Y-m-d'), $date];
    }

    /**
     * Ambil seluruh stok satu gudang dari API (semua halaman), dinormalisasi
     * ke bentuk baris tabel. Di-cache singkat agar aksi sort/filter/paginate
     * tidak memanggil API berulang.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string}  [rows, fetchedAt]
     */
    private function fetchWarehouse(Warehouse $warehouse, bool $fresh, ?CarbonImmutable $asOf = null): array
    {
        // Tanggal as_of ikut jadi bagian kunci cache supaya hasil tiap tanggal
        // tidak tertukar.
        $key = 'inventory_live_wh_' . $warehouse->id . '_asof_' . ($asOf?->format('Ymd') ?? 'now');

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addSeconds(self::CACHE_SECONDS), function () use ($warehouse, $asOf) {
            $client = new WarehouseStockClient($warehouse);
            $rows = [];

            foreach ($client->fetchAsOf($asOf) as $item) {
                if ($item->isDeleted()) {
                    continue;
                }

                $statusKey = $item->qty <= 0
                    ? 'habis'
                    : ($item->qty <= ($item->minQty ?? 0) ? 'menipis' : 'tersedia');

                $rows[] = [
                    'sku'            => $item->sku,
                    'name'           => $item->name,
                    'category'       => $item->category,
                    'uom'            => $item->uom,
                    'qty'            => $item->qty,
                    'min_qty'        => $item->minQty ?? 0,
                    'status_key'     => $statusKey,
                    'status_order'   => $statusKey === 'habis' ? 0 : ($statusKey === 'menipis' ? 1 : 2),
                    'warehouse_code' => $warehouse->code,
                    'warehouse_name' => $warehouse->name,
                    'updated'        => optional($item->sourceUpdatedAt)->setTimezone(config('app.timezone'))->format('Y-m-d H:i'),
                ];
            }

            return [$rows, now()->format('Y-m-d H:i:s')];
        });
    }
}
