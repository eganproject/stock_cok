<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Warehouse;
use App\Services\LiveStockService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const SORTABLE = ['sku', 'name', 'category', 'warehouse', 'stock', 'shortfall', 'status'];

    public function __construct(private readonly LiveStockService $stock)
    {
    }

    /**
     * Laporan Perlu Restock — daftar SKU per gudang yang stoknya di bawah minimum
     * (menipis) atau habis, lengkap dengan kekurangan (min − stok). Per divisi,
     * bisa difilter gudang/kategori/ambang/tanggal, dan diekspor Excel.
     */
    public function restock(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->prepare($request);

        if ($request->boolean('export')) {
            return (new \App\Exports\RestockExport($data))->download();
        }

        $sorted = $data['sorted'];
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $sorted->forPage($page, $data['perPage'])->values(),
            $sorted->count(),
            $data['perPage'],
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.restock', ['items' => $items] + $data);
    }

    /**
     * Laporan Ketimpangan Antar-Gudang — SKU yang di satu gudang kurang (menipis/
     * habis) sementara di gudang lain berlebih, dalam divisi yang sama. Menyorot
     * peluang pemindahan stok antar-gudang.
     */
    public function imbalance(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->prepareImbalance($request);

        if ($request->boolean('export')) {
            return (new \App\Exports\ImbalanceExport($data))->download();
        }

        $sorted = $data['sorted'];
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $sorted->forPage($page, $data['perPage'])->values(),
            $sorted->count(),
            $data['perPage'],
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reports.imbalance', ['items' => $items] + $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareImbalance(Request $request): array
    {
        $divisions = Division::orderBy('name')->get();
        $division = $divisions->firstWhere('id', (int) $request->query('division')) ?? $divisions->first();

        // Ketimpangan selalu memakai SELURUH gudang divisi (butuh perbandingan).
        $divisionWarehouses = $division
            ? Warehouse::where('division_id', $division->id)->where('is_active', true)
                ->orderBy('sequence')->orderBy('name')->get()
            : collect();

        [$asOfRaw, $asOf] = $this->resolveAsOf($request);
        $fresh = $request->boolean('fresh');

        $rows = collect();
        $errors = [];
        $fetchedAt = null;

        foreach ($divisionWarehouses as $warehouse) {
            if (blank($warehouse->base_url)) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): belum dikonfigurasi — Base URL kosong.";
                continue;
            }

            try {
                [$whRows, $time] = $this->stock->warehouseRows($warehouse, $fresh, $asOf);
                $fetchedAt = $fetchedAt === null ? $time : max($fetchedAt, $time);
                foreach ($whRows as $r) {
                    $rows->push($r);
                }
            } catch (\Throwable $e) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): {$e->getMessage()}";
            }
        }

        // Gabung per SKU, hitung surplus/defisit tiap gudang & saran pemindahan.
        $grouped = $rows->groupBy('sku')->map(function (Collection $g) {
            $first = $g->first();

            $perWh = [];
            $totalSurplus = 0.0;
            $totalDeficit = 0.0;
            $total = 0.0;
            $bestSurplus = ['name' => null, 'val' => 0.0];
            $bestDeficit = ['name' => null, 'val' => 0.0];

            foreach ($g as $r) {
                $surplus = max(0.0, (float) $r['qty'] - (float) $r['min_qty']);
                $deficit = max(0.0, (float) $r['min_qty'] - (float) $r['qty']);
                $perWh[$r['warehouse_code']] = [
                    'qty'        => $r['qty'],
                    'min'        => $r['min_qty'],
                    'status_key' => $r['status_key'],
                ];
                $totalSurplus += $surplus;
                $totalDeficit += $deficit;
                $total += (float) $r['qty'];

                if ($surplus > $bestSurplus['val']) {
                    $bestSurplus = ['name' => $r['warehouse_name'], 'val' => $surplus];
                }
                if ($deficit > $bestDeficit['val']) {
                    $bestDeficit = ['name' => $r['warehouse_name'], 'val' => $deficit];
                }
            }

            $rebalance = min($totalSurplus, $totalDeficit);
            $move = ($rebalance > 0 && $bestSurplus['name'] && $bestDeficit['name'] && $bestSurplus['name'] !== $bestDeficit['name'])
                ? ['from' => $bestSurplus['name'], 'to' => $bestDeficit['name'], 'qty' => min($bestSurplus['val'], $bestDeficit['val'])]
                : null;

            return [
                'sku'       => $first['sku'],
                'name'      => $first['name'],
                'category'  => $first['category'],
                'uom'       => $first['uom'],
                'per_wh'    => $perWh,
                'qty'       => $total,
                'imbalance' => $rebalance,
                'move'      => $move,
            ];
        })
            // Hanya SKU timpang: ada gudang kurang DAN ada gudang berlebih.
            ->filter(fn ($r) => $r['imbalance'] > 0 && $r['move'] !== null)
            ->values();

        // ---- Filter ----
        $search   = trim((string) $request->query('search', ''));
        $category = $request->query('category');

        $filtered = $grouped
            ->when($search !== '', fn (Collection $c) => $c->filter(
                fn ($r) => str_contains(mb_strtolower($r['sku'] . ' ' . $r['name'] . ' ' . (string) $r['category']), mb_strtolower($search))
            ))
            ->when($category, fn (Collection $c) => $c->where('category', $category))
            ->values();

        $summary = [
            'items'      => $filtered->count(),
            'units'      => (float) $filtered->sum('imbalance'),
            'warehouses' => $divisionWarehouses->count(),
            'categories' => $filtered->pluck('category')->filter()->unique()->count(),
        ];

        // ---- Urutkan (default ketimpangan terbesar dulu) ----
        $sortable = ['sku', 'name', 'category', 'stock', 'imbalance'];
        $sort      = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'imbalance';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $sortKey = match ($sort) {
            'stock' => 'qty',
            default => $sort,
        };
        $sorted = ($direction === 'desc'
            ? $filtered->sortByDesc($sortKey, SORT_NATURAL | SORT_FLAG_CASE)
            : $filtered->sortBy($sortKey, SORT_NATURAL | SORT_FLAG_CASE)
        )->values();

        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 25;
        $categories = $grouped->pluck('category')->filter()->unique()->sort()->values();

        return compact(
            'divisions', 'division', 'divisionWarehouses',
            'sorted', 'summary', 'categories', 'sort', 'direction', 'perPage',
            'errors', 'fetchedAt', 'asOfRaw'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare(Request $request): array
    {
        $divisions = Division::orderBy('name')->get();
        $division = $divisions->firstWhere('id', (int) $request->query('division')) ?? $divisions->first();

        $divisionWarehouses = $division
            ? Warehouse::where('division_id', $division->id)->where('is_active', true)
                ->orderBy('sequence')->orderBy('name')->get()
            : collect();

        $selectedWarehouse = $divisionWarehouses->firstWhere('id', (int) $request->query('warehouse'));
        $targets = $selectedWarehouse ? collect([$selectedWarehouse]) : $divisionWarehouses;

        [$asOfRaw, $asOf] = $this->resolveAsOf($request);
        $fresh = $request->boolean('fresh');

        $rows = collect();
        $errors = [];
        $fetchedAt = null;

        foreach ($targets as $warehouse) {
            if (blank($warehouse->base_url)) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): belum dikonfigurasi — Base URL kosong.";
                continue;
            }

            try {
                [$whRows, $time] = $this->stock->warehouseRows($warehouse, $fresh, $asOf);
                $fetchedAt = $fetchedAt === null ? $time : max($fetchedAt, $time);
                foreach ($whRows as $r) {
                    $rows->push($r);
                }
            } catch (\Throwable $e) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): {$e->getMessage()}";
            }
        }

        // Hanya yang perlu restock: stok ≤ minimum (menipis atau habis).
        $needing = $rows
            ->filter(fn ($r) => in_array($r['status_key'], ['menipis', 'habis'], true))
            ->map(function ($r) {
                $r['shortfall'] = max(0.0, (float) $r['min_qty'] - (float) $r['qty']);

                return $r;
            })
            ->values();

        // ---- Filter ----
        $search    = trim((string) $request->query('search', ''));
        $category  = $request->query('category');
        $threshold = in_array($request->query('threshold'), ['menipis', 'habis'], true) ? $request->query('threshold') : null;

        $filtered = $needing
            ->when($search !== '', fn (Collection $c) => $c->filter(
                fn ($r) => str_contains(mb_strtolower($r['sku'] . ' ' . $r['name'] . ' ' . (string) $r['category']), mb_strtolower($search))
            ))
            ->when($category, fn (Collection $c) => $c->where('category', $category))
            ->when($threshold, fn (Collection $c) => $c->where('status_key', $threshold))
            ->values();

        $summary = [
            'items'     => $filtered->count(),
            'habis'     => $filtered->where('status_key', 'habis')->count(),
            'menipis'   => $filtered->where('status_key', 'menipis')->count(),
            'shortfall' => (float) $filtered->sum('shortfall'),
        ];

        // ---- Urutkan (default kekurangan terbesar dulu) ----
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'shortfall';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
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
        $categories = $needing->pluck('category')->filter()->unique()->sort()->values();

        return compact(
            'divisions', 'division', 'divisionWarehouses', 'selectedWarehouse',
            'sorted', 'summary', 'categories', 'sort', 'direction', 'perPage',
            'errors', 'fetchedAt', 'asOfRaw', 'threshold'
        );
    }

    /**
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
}
