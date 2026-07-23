<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'warehouse_id', 'product_id', 'qty', 'min_qty',
        'status', 'source_updated_at', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'qty'               => 'float',
            'source_updated_at' => 'datetime',
            'synced_at'         => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Status stok diturunkan dari qty vs batas minimum — bukan kolom tersimpan,
     * supaya selalu konsisten dengan angka terbaru.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->qty <= 0) {
            return 'habis';
        }

        return $this->qty <= $this->min_qty ? 'menipis' : 'tersedia';
    }

    /** Tampilkan qty tanpa desimal bila memang bilangan bulat. */
    public function getQtyFormattedAttribute(): string
    {
        return fmod($this->qty, 1) === 0.0
            ? number_format($this->qty, 0, ',', '.')
            : rtrim(rtrim(number_format($this->qty, 3, ',', '.'), '0'), ',');
    }

    /** Ekspresi SQL untuk mengurutkan berdasarkan status stok. */
    public static function statusOrderExpression(): string
    {
        return 'CASE WHEN stocks.qty <= 0 THEN 0 WHEN stocks.qty <= stocks.min_qty THEN 1 ELSE 2 END';
    }
}
