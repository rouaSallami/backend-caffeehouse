<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\CoffeeController;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Admin\CoffeeController as AdminCoffeeController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| TEST ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/test', function () {
    return response()->json(['message' => 'API working']);
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Coffees
Route::get('/coffees', [CoffeeController::class, 'index']);
Route::get('/coffees/{id}', [CoffeeController::class, 'show']);

// Addons
Route::get('/addons', [AddonController::class, 'index']);

// Reviews
Route::get('/reviews', [ReviewController::class, 'index']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Auth / current user
    Route::get('/me', [MeController::class, 'show']);
    Route::post('/logout', [MeController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);

    // Promo
    Route::get('/promo/me', [PromoController::class, 'me']);

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    // Rewards
    Route::get('/rewards/me', [RewardController::class, 'me']);
    Route::post('/rewards/redeem', [RewardController::class, 'redeem']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Coffees (Admin)
    Route::get('/cafes', [AdminCoffeeController::class, 'index']);
    Route::post('/cafes', [AdminCoffeeController::class, 'store']);
    Route::get('/cafes/{id}', [AdminCoffeeController::class, 'show']);
    Route::put('/cafes/{id}', [AdminCoffeeController::class, 'update']);
    Route::delete('/cafes/{id}', [AdminCoffeeController::class, 'destroy']);
    Route::patch('/cafes/{id}/toggle-availability', [AdminCoffeeController::class, 'toggleAvailability']);
    Route::patch('/cafes/{id}/toggle-new', [AdminCoffeeController::class, 'toggleNew']);

    // Addons (Admin)
    Route::post('/addons', [AddonController::class, 'store']);
    Route::put('/addons/{id}', [AddonController::class, 'update']);
    Route::delete('/addons/{id}', [AddonController::class, 'destroy']);
    Route::patch('/addons/{id}/toggle-availability', [AddonController::class, 'toggleAvailability']);

    // Orders (Admin)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // Users (Admin)
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

    // Reviews (Admin)
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| ADMIN SUMMARY
|--------------------------------------------------------------------------
*/
Route::get('/admin/reviews-summary', function () {
    $reviews = \App\Models\Review::latest()->take(3)->get();

    $total = \App\Models\Review::count();
    $average = \App\Models\Review::avg('rating');

    return response()->json([
        'total' => $total,
        'average' => round($average, 1),
        'latest' => $reviews,
    ]);
});



