<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = [
        'warehouse_id', 'started_at', 'finished_at',
        'status', 'records_processed', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getDurationSecondsAttribute(): ?float
    {
        if (! $this->finished_at) {
            return null;
        }

        return round($this->started_at->diffInMilliseconds($this->finished_at) / 1000, 2);
    }
}
