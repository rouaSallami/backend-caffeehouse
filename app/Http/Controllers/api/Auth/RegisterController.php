<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Promotion;
use App\Models\UserPromotion;


class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        $this->assignWelcomePromotion($user);

        return response()->json([
            'message' => 'Compte créé avec succès',
            'user' => $user,
        ], 201);
    }

    private function assignWelcomePromotion($user): void
    {
        $promoConfig = config('promotions.welcome');

        if (!$promoConfig || empty($promoConfig['code'])) {
            return;
        }

        $promotion = Promotion::firstOrCreate(
            ['code' => $promoConfig['code']],
            [
                'title' => $promoConfig['title'],
                'description' => $promoConfig['description'] ?? null,
                'type' => $promoConfig['type'],
                'value' => $promoConfig['value'],
                'max_discount' => $promoConfig['max_discount'] ?? null,
                'is_active' => true,
                'starts_at' => now(),
                'ends_at' => null,
            ]
        );

        $alreadyAssigned = UserPromotion::where('user_id', $user->id)
            ->where('promotion_id', $promotion->id)
            ->exists();

        if ($alreadyAssigned) {
            return;
        }

        UserPromotion::create([
            'user_id' => $user->id,
            'promotion_id' => $promotion->id,
            'assigned_at' => now(),
            'expires_at' => now()->addDays((int) ($promoConfig['valid_days'] ?? 7)),
            'used_at' => null,
            'order_id' => null,
        ]);
    }

    
}