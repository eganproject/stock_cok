<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

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
        return filled($this->base_url)
            && ($this->auth_type === 'none' || filled($this->api_token));
    }

    /**
     * HTTP client siap pakai untuk memanggil API gudang ini — base URL + auth
     * + timeout sudah terpasang. Dipakai oleh Test Koneksi dan (nanti) sync job.
     *
     * Path endpoint mengikuti kontrak: /api/v1/health dan /api/v1/stocks.
     */
    public function apiRequest(int $timeout = 10): PendingRequest
    {
        $req = Http::baseUrl(rtrim((string) $this->base_url, '/'))
            ->timeout($timeout)
            ->connectTimeout(5)
            ->acceptJson();

        return match ($this->auth_type) {
            'bearer' => filled($this->api_token) ? $req->withToken($this->api_token) : $req,
            'apikey' => filled($this->api_token) ? $req->withHeaders(['X-API-Key' => $this->api_token]) : $req,
            'basic'  => $this->applyBasicAuth($req),
            default  => $req, // 'none'
        };
    }

    private function applyBasicAuth(PendingRequest $req): PendingRequest
    {
        if (blank($this->api_token) || ! str_contains($this->api_token, ':')) {
            return $req;
        }

        [$user, $pass] = explode(':', $this->api_token, 2);

        return $req->withBasicAuth($user, $pass);
    }
}
