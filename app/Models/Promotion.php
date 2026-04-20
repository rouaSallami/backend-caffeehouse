<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'value',
        'max_discount',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function userPromotions()
    {
        return $this->hasMany(UserPromotion::class);
    }
}