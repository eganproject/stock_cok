<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /** Katalog produk milik divisi ini (terpisah dari divisi lain). */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
