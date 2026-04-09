<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class LoginController extends Controller
{
    public function store(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'Email ou mot de passe incorrect'
        ], 401);
    }

    $user = Auth::user();

    if (!$user->active) {
        Auth::logout();
        return response()->json([
            'message' => 'Compte inactif'
        ], 403);
    }

    
    $token = $user->createToken('auth_token')->plainTextToken;

return response()->json([
    'message' => 'Connexion réussie',
    'token' => $token,
    'user' => $user,
]);
}
}