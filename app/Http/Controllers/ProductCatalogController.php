<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Warehouse;
use App\Sync\WarehouseStockClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Master Produk — katalog produk (baca-saja) yang ditarik LANGSUNG dari API
 * gudang per divisi. Satu baris per SKU unik (digabung dari seluruh gudang
 * divisi), tanpa rincian stok per gudang. Konsisten dengan halaman Inventory
 * yang juga live.
 */
class ProductCatalogController extends Controller
{
    private const SORTABLE = ['sku', 'name', 'category', 'uom', 'warehouses', 'stock'];

    /** Cache singkat agar aksi sort/filter/paginate tidak menembak API berulang. */
    private const CACHE_SECONDS = 120;

    public function index(Request $request): View
    {
        $divisions = Division::orderBy('name')->get();

        // Katalog di-scope per divisi (tunggal) agar volume data dari API terkendali.
        $division = $divisions->firstWhere('id', (int) $request->query('division')) ?? $divisions->first();

        $divisionWarehouses = $division
            ? Warehouse::where('division_id', $division->id)->where('is_active', true)
                ->orderBy('sequence')->orderBy('name')->get()
            : collect();

        // Tarik data live tiap gudang lalu gabungkan per SKU.
        $fresh = $request->boolean('fresh');
        $errors = [];
        $fetchedAt = null;

        /** @var array<string, array<string, mixed>> $bySku */
        $bySku = [];

        foreach ($divisionWarehouses as $warehouse) {
            if (blank($warehouse->base_url)) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): belum dikonfigurasi — Base URL kosong.";
                continue;
            }

            try {
                [$items, $time] = $this->fetchWarehouse($warehouse, $fresh);
                $fetchedAt = $fetchedAt === null ? $time : max($fetchedAt, $time);

                foreach ($items as $it) {
                    $sku = $it['sku'];
                    if (! isset($bySku[$sku])) {
                        $bySku[$sku] = [
                            'sku'        => $sku,
                            'name'       => $it['name'],
                            'category'   => $it['category'],
                            'uom'        => $it['uom'],
                            'warehouses' => [],
                            'stock'      => 0.0,
                        ];
                    }

                    // Lengkapi metadata bila baris pertama kebetulan kosong.
                    $bySku[$sku]['name'] = $bySku[$sku]['name'] ?: $it['name'];
                    $bySku[$sku]['category'] = $bySku[$sku]['category'] ?? $it['category'];
                    $bySku[$sku]['uom'] = $bySku[$sku]['uom'] ?: $it['uom'];

                    $bySku[$sku]['warehouses'][$warehouse->code] = $warehouse->name;
                    $bySku[$sku]['stock'] += (float) $it['qty'];
                }
            } catch (\Throwable $e) {
                $errors[] = "{$warehouse->name} ({$warehouse->code}): {$e->getMessage()}";
            }
        }

        $rows = collect(array_values($bySku))->map(function (array $r) {
            $r['warehouse_count'] = count($r['warehouses']);
            $r['warehouse_names'] = implode(', ', $r['warehouses']);

            return $r;
        });

        // ---- Filter di memori ----
        $search   = trim((string) $request->query('search', ''));
        $category = $request->query('category');

        $filtered = $rows
            ->when($search !== '', fn (Collection $c) => $c->filter(
                fn ($r) => str_contains(mb_strtolower($r['sku'] . ' ' . $r['name']), mb_strtolower($search))
            ))
            ->when($category, fn (Collection $c) => $c->where('category', $category))
            ->values();

        $summary = [
            'products'   => $filtered->count(),
            'categories' => $filtered->pluck('category')->filter()->unique()->count(),
            'warehouses' => $divisionWarehouses->count(),
            'stock'      => (float) $filtered->sum('stock'),
        ];

        // ---- Urutkan di memori ----
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'sku';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $sortKey = match ($sort) {
            'warehouses' => 'warehouse_count',
            default      => $sort,
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

        return view('products.index', compact(
            'divisions', 'division', 'divisionWarehouses',
            'items', 'summary', 'categories', 'sort', 'direction', 'perPage',
            'errors', 'fetchedAt'
        ));
    }

    /**
     * Ambil stok terkini satu gudang dari API (semua halaman), dinormalisasi ke
     * baris ringkas. Di-cache singkat agar sort/filter/paginate tidak memanggil
     * API berulang.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string}  [rows, fetchedAt]
     */
    private function fetchWarehouse(Warehouse $warehouse, bool $fresh): array
    {
        $key = 'catalog_live_wh_' . $warehouse->id;

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addSeconds(self::CACHE_SECONDS), function () use ($warehouse) {
            $client = new WarehouseStockClient($warehouse);
            $rows = [];

            foreach ($client->fetchAsOf(null) as $item) {
                if ($item->isDeleted()) {
                    continue;
                }

                $rows[] = [
                    'sku'      => $item->sku,
                    'name'     => $item->name,
                    'category' => $item->category,
                    'uom'      => $item->uom,
                    'qty'      => $item->qty,
                ];
            }

            return [$rows, now()->format('Y-m-d H:i:s')];
        });
    }
}
