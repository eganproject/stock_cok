<?php

namespace App\Jobs;

use App\Models\Warehouse;
use App\Sync\StockSyncService;
use App\Sync\SyncException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncWarehouseStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Coba ulang untuk gangguan sementara (timeout, 5xx). */
    public int $tries = 3;

    /** Jeda antar percobaan (detik) — makin lama tiap gagal. */
    public array $backoff = [30, 120, 300];

    /** Batas waktu satu percobaan. */
    public int $timeout = 120;

    public function __construct(
        public int $warehouseId,
        public bool $full = false,
    ) {
    }

    /**
     * Cegah dua sinkronisasi gudang yang sama berjalan bersamaan.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('sync-warehouse-' . $this->warehouseId))->dontRelease()];
    }

    public function handle(StockSyncService $service): void
    {
        $warehouse = Warehouse::find($this->warehouseId);

        if (! $warehouse || ! $warehouse->is_active || blank($warehouse->base_url)) {
            return; // gudang dihapus / dinonaktifkan / belum dikonfigurasi
        }

        $result = $service->sync($warehouse, dryRun: false, full: $this->full);

        // Lempar exception agar mekanisme retry/backoff antrian bekerja.
        if (! $result->ok) {
            throw new SyncException($result->error ?? 'Sinkronisasi gagal tanpa keterangan.');
        }
    }
}
