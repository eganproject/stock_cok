<?php

namespace App\Sync;

use App\Models\Product;
use App\Models\Stock;
use App\Models\SyncLog;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Orkestrasi sinkronisasi stok satu gudang: tarik data lewat
 * WarehouseStockClient, upsert products + stocks, catat sync_logs, dan
 * perbarui status gudang.
 *
 * Sinkronisasi bersifat idempoten (upsert per SKU), sehingga aman dijalankan
 * ulang. Mode dry-run hanya menarik + memvalidasi tanpa menyentuh DB.
 */
class StockSyncService
{
    public function sync(Warehouse $warehouse, bool $dryRun = false, bool $full = false): SyncResult
    {
        // Incremental: hanya data yang berubah sejak sinkronisasi terakhir.
        // Full: tarik semua (dipakai saat pertama kali atau rekonsiliasi).
        $since = ($full || $warehouse->last_synced_at === null)
            ? null
            : $warehouse->last_synced_at->toImmutable();

        $log = null;
        if (! $dryRun) {
            $warehouse->update(['sync_status' => 'running']);
            $log = SyncLog::create([
                'warehouse_id' => $warehouse->id,
                'started_at'   => now(),
                'status'       => 'running',
            ]);
        }

        $client = new WarehouseStockClient($warehouse);
        $counts = ['processed' => 0, 'new_products' => 0, 'active' => 0, 'inactive' => 0, 'deleted' => 0];

        try {
            foreach ($client->fetch($since) as $item) {
                $counts['processed']++;
                $counts[$item->status]++;

                if (! $dryRun) {
                    $this->upsert($warehouse, $item, $counts);
                }
            }

            // Watermark untuk sync berikutnya = waktu server gudang (kontrak 2.4).
            $watermark = $client->serverTime() ?? CarbonImmutable::now();

            if (! $dryRun) {
                $warehouse->update([
                    'sync_status'    => 'success',
                    'last_synced_at' => $watermark,
                    'last_error'     => null,
                ]);
                $log?->update([
                    'status'            => 'success',
                    'finished_at'       => now(),
                    'records_processed' => $counts['processed'],
                ]);
            }

            return new SyncResult(true, $counts, null, $dryRun);
        } catch (\Throwable $e) {
            if (! $dryRun) {
                $warehouse->update([
                    'sync_status' => 'failed',
                    'last_error'  => Str::limit($e->getMessage(), 480),
                ]);
                $log?->update([
                    'status'            => 'failed',
                    'finished_at'       => now(),
                    'records_processed' => $counts['processed'],
                    'error_message'     => Str::limit($e->getMessage(), 990),
                ]);
            }

            return new SyncResult(false, $counts, $e->getMessage(), $dryRun);
        }
    }

    private function upsert(Warehouse $warehouse, StockItemData $item, array &$counts): void
    {
        // firstOrCreate: data produk (nama/kategori/uom) hanya diisi saat pertama
        // kali dibuat, sehingga koreksi manual dari pusat tidak tertimpa sync.
        $product = Product::firstOrCreate(
            ['division_id' => $warehouse->division_id, 'sku' => $item->sku],
            [
                'name'     => $item->name,
                'category' => $item->category,
                'uom'      => $item->uom,
                'min_qty'  => $item->minQty ?? 0,
            ]
        );

        if ($product->wasRecentlyCreated) {
            $counts['new_products']++;
        }

        Stock::updateOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            [
                'qty'               => $item->qty,
                'min_qty'           => $item->minQty ?? $product->min_qty ?? 0,
                'status'            => $item->status,
                // Normalkan ke zona waktu aplikasi (WIB) agar konsisten disimpan & ditampilkan.
                'source_updated_at' => $item->sourceUpdatedAt->setTimezone(config('app.timezone')),
                'synced_at'         => now(),
            ]
        );
    }
}
