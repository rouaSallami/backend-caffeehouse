<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'mode',
        'notes',
        'total_price',
        'status',
        'completed_at',
        'loyalty_points_awarded_at',
        'is_archived',
        'subtotal_price',
'discount_amount',
'applied_promo_code',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'loyalty_points_awarded_at' => 'datetime',
        'is_archived' => 'boolean',
        'subtotal_price' => 'decimal:2',
'discount_amount' => 'decimal:2',
'total_price' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userPromotions()
{
    return $this->hasMany(\App\Models\UserPromotion::class);
}


protected function casts(): array
{
    return [
        'is_archived' => 'boolean',
        'completed_at' => 'datetime',
        'subtotal_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];
}

}