<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'division_id', 'code', 'name', 'address', 'capacity',
        'base_url', 'auth_type', 'api_token', 'timezone',
        'is_active', 'last_synced_at', 'sync_status', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'last_synced_at' => 'datetime',
            'api_token'      => 'encrypted',
        ];
    }

    protected $hidden = ['api_token'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    /** Persentase keterisian gudang berdasarkan total qty vs kapasitas. */
    public function fillPercentage(?float $totalQty = null): ?int
    {
        if (! $this->capacity) {
            return null;
        }

        $qty = $totalQty ?? (float) $this->stocks()->sum('qty');

        return (int) min(100, round($qty / $this->capacity * 100));
    }

    public function isConfigured(): bool
    {
        return filled($this->base_url) && filled($this->api_token);
    }
}
