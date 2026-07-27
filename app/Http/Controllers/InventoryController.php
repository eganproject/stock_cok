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
        $divisions = Division::orderBy('name')->get();

        // Data inventory diambil LANGSUNG dari API gudang, di-scope per divisi.
        // Filter divisi selalu tunggal agar volume data dari API terkendali.
        $division = $divisions->firstWhere('id', (int) $request->query('division')) ?? $divisions->first();

        // Gudang aktif pada divisi terpilih (untuk dropdown + target pemanggilan API).
        $divisionWarehouses = $division
            ? Warehouse::where('division_id', $division->id)->where('is_active', true)->orderBy('name')->get()
            : collect();

        // Gudang terpilih harus milik divisi ini; kosong = semua gudang divisi.
        $selectedWarehouse = $divisionWarehouses->firstWhere('id', (int) $request->query('warehouse'));
        $targets = $selectedWarehouse ? collect([$selectedWarehouse]) : $divisionWarehouses;

        // Filter tanggal (opsional) diteruskan ke API sebagai updated_since/until.
        // Ini menyaring berdasarkan KAPAN barang terakhir berubah (updated_at),
        // bukan "posisi stok pada tanggal itu".
        [$dateFrom, $dateTo, $since, $until] = $this->resolveDateRange($request);

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
                [$items, $time] = $this->fetchWarehouse($warehouse, $fresh, $since, $until);
                $fetchedAt = $fetchedAt === null ? $time : max($fetchedAt, $time);
                foreach ($items as $item) {
                    $rows->push($item);
                }
            } catch (\Throwable $e) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): {$e->getMessage()}";
            }
        }

        // ---- Filter di memori ----
        $search   = trim((string) $request->query('search', ''));
        $category = $request->query('category');
        $status   = $request->query('status');

        $filtered = $rows
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
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
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

        // ---- Paginasi di memori ----
        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 25;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = $rows->pluck('category')->filter()->unique()->sort()->values();

        return view('inventory.index', compact(
            'divisions', 'division', 'divisionWarehouses', 'selectedWarehouse',
            'items', 'summary', 'categories', 'sort', 'direction', 'perPage',
            'errors', 'fetchedAt', 'targets', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Ubah query date_from/date_to (Y-m-d) menjadi rentang instan untuk API:
     * awal hari s/d akhir hari (WIB). Mengembalikan [rawFrom, rawTo, since, until].
     *
     * @return array{0: ?string, 1: ?string, 2: ?CarbonImmutable, 3: ?CarbonImmutable}
     */
    private function resolveDateRange(Request $request): array
    {
        $tz = config('app.timezone');
        $parse = function (?string $value) use ($tz): ?CarbonImmutable {
            if (blank($value)) {
                return null;
            }
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $value, $tz) ?: null;
            } catch (\Throwable) {
                return null;
            }
        };

        $from = $parse($request->query('date_from'));
        $to   = $parse($request->query('date_to'));

        return [
            $from?->format('Y-m-d'),
            $to?->format('Y-m-d'),
            $from?->startOfDay(),
            $to?->endOfDay(),
        ];
    }

    /**
     * Ambil seluruh stok satu gudang dari API (semua halaman), dinormalisasi
     * ke bentuk baris tabel. Di-cache singkat agar aksi sort/filter/paginate
     * tidak memanggil API berulang.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string}  [rows, fetchedAt]
     */
    private function fetchWarehouse(Warehouse $warehouse, bool $fresh, ?CarbonImmutable $since = null, ?CarbonImmutable $until = null): array
    {
        // Rentang tanggal ikut jadi bagian kunci cache supaya hasil per rentang
        // tidak tertukar.
        $key = 'inventory_live_wh_' . $warehouse->id
            . '_' . ($since?->timestamp ?? 0)
            . '_' . ($until?->timestamp ?? 0);

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addSeconds(self::CACHE_SECONDS), function () use ($warehouse, $since, $until) {
            $client = new WarehouseStockClient($warehouse);
            $rows = [];

            foreach ($client->fetch($since, $until) as $item) {
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
