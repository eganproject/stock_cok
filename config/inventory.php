<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jadwal Sinkronisasi Otomatis
    |--------------------------------------------------------------------------
    | Bila true, jadwal di routes/console.php akan mengirim job sinkronisasi
    | secara berkala. Biarkan false sampai queue worker & cron siap.
    */
    'sync_schedule_enabled' => env('SYNC_SCHEDULE_ENABLED', false),

];
