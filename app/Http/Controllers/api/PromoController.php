<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $welcomeCode = config('promotions.welcome.code', 'WELCOME25');

        $activePromo = $user->userPromotions()
            ->with('promotion')
            ->whereHas('promotion', function ($q) use ($welcomeCode) {
                $q->where('code', $welcomeCode)
                  ->where('is_active', true);
            })
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if ($activePromo && $activePromo->promotion) {
            $promo = $activePromo->promotion;

            return response()->json([
                'has_active_promo' => true,
                'has_used_promo' => false,
                'promo' => [
                    'code' => $promo->code,
                    'title' => $promo->title,
                    'description' => $promo->description,
                    'type' => $promo->type,
                    'value' => (float) $promo->value,
                    'max_discount' => $promo->max_discount !== null
                        ? (float) $promo->max_discount
                        : null,
                    'expires_at' => $activePromo->expires_at,
                ],
            ]);
        }

        $usedPromo = $user->userPromotions()
            ->whereHas('promotion', function ($q) use ($welcomeCode) {
                $q->where('code', $welcomeCode);
            })
            ->whereNotNull('used_at')
            ->exists();

        return response()->json([
            'has_active_promo' => false,
            'has_used_promo' => $usedPromo,
            'promo' => null,
        ]);
    }
}