<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPromotion extends Model
{
    protected $fillable = [
        'user_id',
        'promotion_id',
        'assigned_at',
        'expires_at',
        'used_at',
        'order_id',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}