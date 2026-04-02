<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoffeeSize extends Model
{
    protected $fillable = [
        'coffee_id',
        'key',
        'label',
        'price',
    ];

    public function coffee()
    {
        return $this->belongsTo(Coffee::class);
    }
}