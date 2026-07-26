<?php

namespace App\Sync;

use RuntimeException;

/** Kegagalan yang diketahui saat sinkronisasi (bukan bug internal). */
class SyncException extends RuntimeException
{
}
