<?php

use App\Jobs\SyncWarehouseStockJob;
use App\Models\Warehouse;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Jadwal Sinkronisasi Stok
|--------------------------------------------------------------------------
| DIMATIKAN secara default (SYNC_SCHEDULE_ENABLED=false). Aktifkan hanya
| setelah: (1) gudang dikonfigurasi & lolos Test Koneksi, (2) queue worker
| berjalan, (3) cron "schedule:run" terpasang di server.
|
| Saat aktif:
|   - tiap 10 menit : sinkronisasi incremental semua gudang aktif
|   - tiap hari 02:15: rekonsiliasi penuh (menangkap data yang terlewat)
*/
if (config('inventory.sync_schedule_enabled')) {
    Schedule::call(function () {
        Warehouse::query()
            ->where('is_active', true)
            ->whereNotNull('base_url')
            ->get()
            ->each(fn (Warehouse $w) => SyncWarehouseStockJob::dispatch($w->id));
    })->everyTenMinutes()->name('sync-stok-incremental')->withoutOverlapping();

    Schedule::call(function () {
        Warehouse::query()
            ->where('is_active', true)
            ->whereNotNull('base_url')
            ->get()
            ->each(fn (Warehouse $w) => SyncWarehouseStockJob::dispatch($w->id, full: true));
    })->dailyAt('02:15')->name('sync-stok-rekonsiliasi')->withoutOverlapping();
}
