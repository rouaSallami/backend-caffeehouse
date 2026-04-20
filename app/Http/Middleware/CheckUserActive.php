<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class CheckUserActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

       if (Auth::check() && !Auth::user()->active) {

    Order::where('user_id', Auth::id())
        ->where('is_archived', false)
        ->update([
            'is_archived' => true,
            'status' => 'cancelled',
        ]);

    return response()->json([
        'message' => 'Account inactive'
    ], 403);
}

        return $next($request);
    }
}