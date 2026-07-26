<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\SyncLog;
use App\Models\Warehouse;
use App\Sync\StockSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SyncStatusController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::query()
            ->with('division')
            ->withCount(['stocks as stocks_count' => fn ($q) => $q->where('status', '!=', 'deleted')])
            ->orderBy('code')
            ->get();

        // Log terakhir per gudang, untuk menampilkan durasi & jumlah baris.
        $lastLogs = SyncLog::query()
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($group) => $group->sortByDesc('started_at')->first());

        // Riwayat sinkronisasi terbaru (semua gudang).
        $logs = SyncLog::query()
            ->with('warehouse')
            ->latest('started_at')
            ->paginate(12);

        $configured = $warehouses->filter->isConfigured();

        $stats = [
            'configured' => $configured->count(),
            'success'    => $warehouses->where('sync_status', 'success')->count(),
            'failed'     => $warehouses->where('sync_status', 'failed')->count(),
            'items'      => Stock::where('status', '!=', 'deleted')->count(),
        ];

        return view('sync.index', compact('warehouses', 'lastLogs', 'logs', 'stats'));
    }

    /**
     * Jalankan sinkronisasi satu gudang secara langsung (synchronous), agar
     * bisa dipicu dari browser tanpa queue worker — cocok untuk shared hosting.
     */
    public function run(Warehouse $warehouse, StockSyncService $service): RedirectResponse
    {
        if (blank($warehouse->base_url)) {
            return back()->with('error', "Gudang {$warehouse->code} belum dikonfigurasi (Base URL kosong).");
        }

        if (! $warehouse->is_active) {
            return back()->with('error', "Gudang {$warehouse->code} nonaktif.");
        }

        $result = $service->sync($warehouse);
        $c = $result->counts;

        if ($result->ok) {
            return back()->with('success', sprintf(
                'Sinkronisasi %s berhasil — %d baris diproses (+%d produk baru).',
                $warehouse->code, $c['processed'], $c['new_products']
            ));
        }

        return back()->with('error', "Sinkronisasi {$warehouse->code} gagal: {$result->error}");
    }
}
