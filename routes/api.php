<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CoffeeController;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\OrderController;



Route::get('/test', function () {
    return response()->json([
        'message' => 'API working'
    ]);
});



Route::get('/coffees', [CoffeeController::class, 'index']);

Route::get('/coffees/{id}', [CoffeeController::class, 'show']);

Route::get('/addons', [AddonController::class, 'index']);
Route::post('/addons', [AddonController::class, 'store']);
Route::put('/addons/{id}', [AddonController::class, 'update']);
Route::delete('/addons/{id}', [AddonController::class, 'destroy']);
Route::patch('/addons/{id}/toggle-availability', [AddonController::class, 'toggleAvailability']);

Route::post('/login', [LoginController::class, 'store']);

Route::post('/register', [RegisterController::class, 'store']);

Route::get('/admin/test', function () {
    return response()->json([
        'message' => 'Bienvenue admin'
    ]);
});


Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::delete('/orders/{id}', [OrderController::class, 'destroy']);






use App\Http\Controllers\Api\Admin\CoffeeController as AdminCoffeeController;

// Public routes
Route::get('/cafes', [CoffeeController::class, 'index']);
Route::get('/cafes/{id}', [CoffeeController::class, 'show']);

// Admin routes
Route::prefix('admin')->group(function () {
    Route::get('/cafes', [AdminCoffeeController::class, 'index']);
    Route::post('/cafes', [AdminCoffeeController::class, 'store']);
    Route::get('/cafes/{id}', [AdminCoffeeController::class, 'show']);
    Route::put('/cafes/{id}', [AdminCoffeeController::class, 'update']);
    Route::delete('/cafes/{id}', [AdminCoffeeController::class, 'destroy']);

    Route::patch('/cafes/{id}/toggle-availability', [AdminCoffeeController::class, 'toggleAvailability']);
    Route::patch('/cafes/{id}/toggle-new', [AdminCoffeeController::class, 'toggleNew']);
});



use App\Http\Controllers\Api\UserController;

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);