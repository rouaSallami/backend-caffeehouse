<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'coffee_id',
        'size_name',
        'sugar',
        'container',
        'milk',
        'note',
        'addons',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'addons' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function coffee()
    {
        return $this->belongsTo(Coffee::class);
    }
}