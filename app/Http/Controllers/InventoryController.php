<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /** Kolom yang boleh dipakai untuk sorting => ekspresi SQL-nya. */
    private const SORT_MAP = [
        'sku'       => 'products.sku',
        'name'      => 'products.name',
        'category'  => 'products.category',
        'warehouse' => 'warehouses.name',
        'stock'     => 'stocks.qty',
        'status'    => null, // ditangani khusus (ekspresi CASE)
        'updated'   => 'stocks.source_updated_at',
    ];

    public function index(Request $request): View
    {
        $sort      = array_key_exists($request->query('sort'), self::SORT_MAP) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage   = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 10;

        $search      = trim((string) $request->query('search', ''));
        $divisionId  = $request->query('division');
        $warehouseId = $request->query('warehouse');
        $category    = $request->query('category');
        $status      = $request->query('status');
        $dateFrom    = $request->query('date_from');
        $dateTo      = $request->query('date_to');

        $base = Stock::query()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->join('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
            ->where('stocks.status', '!=', 'deleted')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('products.sku', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('products.category', 'like', "%{$search}%");
                });
            })
            ->when($divisionId, fn ($q) => $q->where('warehouses.division_id', $divisionId))
            ->when($warehouseId, fn ($q) => $q->where('stocks.warehouse_id', $warehouseId))
            ->when($category, fn ($q) => $q->where('products.category', $category))
            ->when($status === 'habis', fn ($q) => $q->where('stocks.qty', '<=', 0))
            ->when($status === 'menipis', fn ($q) => $q->where('stocks.qty', '>', 0)->whereColumn('stocks.qty', '<=', 'stocks.min_qty'))
            ->when($status === 'tersedia', fn ($q) => $q->whereColumn('stocks.qty', '>', 'stocks.min_qty'))
            ->when($dateFrom, fn ($q) => $q->whereDate('stocks.source_updated_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('stocks.source_updated_at', '<=', $dateTo));

        // Ringkasan mengikuti filter aktif
        $summary = [
            'items' => (clone $base)->count(),
            'stock' => (float) (clone $base)->sum('stocks.qty'),
            'low'   => (clone $base)->where('stocks.qty', '>', 0)->whereColumn('stocks.qty', '<=', 'stocks.min_qty')->count(),
            'out'   => (clone $base)->where('stocks.qty', '<=', 0)->count(),
        ];

        $items = (clone $base)
            ->select('stocks.*')
            ->with(['product', 'warehouse.division'])
            ->when(
                $sort === 'status',
                fn ($q) => $q->orderByRaw(Stock::statusOrderExpression() . ' ' . $direction),
                fn ($q) => $q->orderBy(self::SORT_MAP[$sort], $direction)
            )
            ->paginate($perPage)
            ->withQueryString();

        $divisions  = Division::orderBy('name')->get();
        $warehouses = Warehouse::with('division')->orderBy('name')->get()->groupBy('division.name');
        $categories = Product::query()
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('inventory.index', compact(
            'items', 'summary', 'divisions', 'warehouses', 'categories',
            'sort', 'direction', 'perPage'
        ));
    }
}
