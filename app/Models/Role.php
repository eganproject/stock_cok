<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_locked'];

    protected function casts(): array
    {
        return ['is_locked' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }
}
