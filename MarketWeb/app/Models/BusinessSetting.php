<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSetting extends Model
{
    protected $fillable = [
        'store_id',
        'business_name',
        'rut',
        'address',
        'phone',
        'email',
        'currency',
        'logo_path',
        'receipt_message',
        'vendor_business_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'vendor_business_completed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
