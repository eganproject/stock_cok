<?php

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
| setelah: (1) gudang dikonfigurasi & lolos Test Koneksi, (2) cron
| "schedule:run" terpasang di server (lihat docs/deploy-hostinger.md).
|
| Menjalankan command `warehouse:sync` secara LANGSUNG (synchronous), jadi
| TIDAK memerlukan queue worker — cocok untuk shared hosting. Command sudah
| menangani tiap gudang secara terpisah (kegagalan satu gudang tidak
| menghentikan yang lain).
|
| Saat aktif:
|   - tiap 10 menit : sinkronisasi incremental semua gudang aktif
|   - tiap hari 02:15: rekonsiliasi penuh (menangkap data yang terlewat)
*/
if (config('inventory.sync_schedule_enabled')) {
    Schedule::command('warehouse:sync')
        ->everyTenMinutes()
        ->withoutOverlapping()
        ->name('sync-stok-incremental');

    Schedule::command('warehouse:sync --full')
        ->dailyAt('02:15')
        ->withoutOverlapping()
        ->name('sync-stok-rekonsiliasi');
}
