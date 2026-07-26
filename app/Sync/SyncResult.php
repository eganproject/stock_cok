<?php

namespace App\Sync;

final class SyncResult
{
    /**
     * @param array{processed:int,new_products:int,active:int,inactive:int,deleted:int} $counts
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $counts,
        public readonly ?string $error = null,
        public readonly bool $dryRun = false,
    ) {
    }
}
