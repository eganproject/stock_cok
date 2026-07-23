<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $divisionId = $request->query('division');
        $divisions  = Division::orderBy('name')->get();

        $stockQuery = fn () => Stock::query()
            ->join('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
            ->where('stocks.status', '!=', 'deleted')
            ->when($divisionId, fn ($q) => $q->where('warehouses.division_id', $divisionId));

        $stats = [
            'total_items' => Product::query()
                ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
                ->count(),
            'total_stock' => (float) $stockQuery()->sum('stocks.qty'),
            'low_stock'   => $stockQuery()->whereColumn('stocks.qty', '<=', 'stocks.min_qty')->count(),
            'warehouses'  => Warehouse::where('is_active', true)
                ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
                ->count(),
        ];

        // Total stok per gudang — dipakai untuk grafik & bar kapasitas
        $perWarehouse = Warehouse::query()
            ->with('division')
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->leftJoin('stocks', function ($join) {
                $join->on('stocks.warehouse_id', '=', 'warehouses.id')
                     ->where('stocks.status', '!=', 'deleted');
            })
            ->select('warehouses.*', DB::raw('COALESCE(SUM(stocks.qty), 0) as total_qty'))
            ->groupBy('warehouses.id')
            ->orderByDesc('total_qty')
            ->get();

        $warehouses = $perWarehouse->map(fn (Warehouse $w) => [
            'name'     => $w->name,
            'code'     => $w->code,
            'division' => $w->division->code,
            'stock'    => (float) $w->total_qty,
            'capacity' => $w->fillPercentage((float) $w->total_qty),
        ]);

        $chart = [
            'labels' => $perWarehouse->pluck('code')->all(),
            'values' => $perWarehouse->map(fn ($w) => (float) $w->total_qty)->all(),
        ];

        // Barang paling mendesak untuk restock
        $lowStockItems = Stock::query()
            ->join('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
            ->where('stocks.status', '!=', 'deleted')
            ->when($divisionId, fn ($q) => $q->where('warehouses.division_id', $divisionId))
            ->whereColumn('stocks.qty', '<=', 'stocks.min_qty')
            ->with(['product', 'warehouse'])
            ->select('stocks.*')
            ->orderBy('stocks.qty')
            ->limit(6)
            ->get();

        $totalUsers = User::count();

        return view('dashboard', compact(
            'stats', 'chart', 'warehouses', 'lowStockItems', 'totalUsers', 'divisions', 'divisionId'
        ));
    }
}
