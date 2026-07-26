<?php

namespace App\Console\Commands;

use App\Jobs\SyncWarehouseStockJob;
use App\Models\Warehouse;
use App\Sync\StockSyncService;
use Illuminate\Console\Command;

class SyncWarehouseCommand extends Command
{
    protected $signature = 'warehouse:sync
        {code? : Kode gudang tertentu (kosongkan untuk semua gudang aktif)}
        {--dry-run : Tarik & validasi data tanpa menyimpan ke database}
        {--full : Tarik seluruh data, abaikan penanda sinkronisasi terakhir}
        {--queue : Jalankan lewat antrian (background), bukan langsung}';

    protected $description = 'Sinkronkan stok dari API gudang ke database lokal';

    public function handle(StockSyncService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $full   = (bool) $this->option('full');
        $queue  = (bool) $this->option('queue') && ! $dryRun;

        $warehouses = $this->resolveWarehouses();

        if ($warehouses->isEmpty()) {
            $this->warn('Tidak ada gudang yang cocok / terkonfigurasi (base_url kosong).');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('MODE DRY-RUN — tidak ada data yang disimpan.');
        }

        $hadFailure = false;

        foreach ($warehouses as $warehouse) {
            if ($queue) {
                SyncWarehouseStockJob::dispatch($warehouse->id, $full);
                $this->line("  [{$warehouse->code}] dikirim ke antrian.");

                continue;
            }

            $this->line("→ Sinkronisasi <info>{$warehouse->code}</info> ({$warehouse->name})…");
            $result = $service->sync($warehouse, dryRun: $dryRun, full: $full);
            $c = $result->counts;

            if ($result->ok) {
                $this->line(sprintf(
                    '  OK — %d baris | +%d produk baru | aktif %d, nonaktif %d, dihapus %d',
                    $c['processed'], $c['new_products'], $c['active'], $c['inactive'], $c['deleted']
                ));
            } else {
                $hadFailure = true;
                $this->error("  GAGAL setelah {$c['processed']} baris: {$result->error}");
            }
        }

        return $hadFailure ? self::FAILURE : self::SUCCESS;
    }

    private function resolveWarehouses()
    {
        if ($code = $this->argument('code')) {
            $warehouse = Warehouse::where('code', $code)->first();

            if (! $warehouse) {
                $this->error("Gudang dengan kode \"{$code}\" tidak ditemukan.");

                return collect();
            }

            if (blank($warehouse->base_url)) {
                $this->error("Gudang \"{$code}\" belum punya base URL.");

                return collect();
            }

            return collect([$warehouse]);
        }

        return Warehouse::query()
            ->where('is_active', true)
            ->whereNotNull('base_url')
            ->orderBy('code')
            ->get();
    }
}
