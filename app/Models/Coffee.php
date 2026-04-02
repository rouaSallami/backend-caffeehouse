<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coffee extends Model
{
    protected $fillable = [
        'name',
        'category',
        'image',
        'available',
        'is_new',
        'description',
    ];

    public function sizes()
    {
        return $this->hasMany(CoffeeSize::class);
    }

    public function ingredients()
    {
        return $this->hasMany(CoffeeIngredient::class);
    }
    public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}
}