<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoffeeIngredient extends Model
{
    protected $fillable = [
        'coffee_id',
        'name',
    ];

    public function coffee()
    {
        return $this->belongsTo(Coffee::class);
    }
}