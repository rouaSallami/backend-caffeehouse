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
        'is_archived',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}