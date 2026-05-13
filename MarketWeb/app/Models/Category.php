<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['store_id', 'local_id', 'name', 'status'];

    protected $casts = ['status' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if ($category->store_id === null && auth()->check() && auth()->user()->store_id) {
                $category->store_id = auth()->user()->store_id;
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
