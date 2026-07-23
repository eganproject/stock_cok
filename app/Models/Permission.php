<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'group', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Permission $permission) {
            if (empty($permission->slug)) {
                $permission->slug = Str::slug($permission->name);
            }
        });
    }
}
