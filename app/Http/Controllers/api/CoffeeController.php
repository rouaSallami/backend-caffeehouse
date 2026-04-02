<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coffee;
use App\Http\Resources\CoffeeResource;

class CoffeeController extends Controller
{
    public function index()
{
    $coffees = Coffee::with(['sizes', 'ingredients'])->get();

    return response()->json(
        CoffeeResource::collection($coffees)->resolve()
    );
}

    public function show($id)
{
    $coffee = Coffee::with(['sizes', 'ingredients'])->findOrFail($id);

    return response()->json(
        (new CoffeeResource($coffee))->resolve()
    );
}
}