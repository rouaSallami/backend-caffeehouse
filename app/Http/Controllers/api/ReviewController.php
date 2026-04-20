<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // GET /reviews
    public function index()
{
    $reviews = Review::with('user')->latest()->get();

    return $reviews->map(function ($review) {
        $hasDeliveredOrder = \App\Models\Order::where('user_id', $review->user_id)
            ->where('status', 'delivered')
            ->exists();

        return [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'user_name' => $review->user_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at,
            'is_verified' => $hasDeliveredOrder, // ⭐ المهم
        ];
    });
}

    // POST /reviews

public function store(Request $request)
{
    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'required|string|min:3',
    ]);

    $user = $request->user();

    // 🔒 تحقق: هل user عندو commande livrée
    $hasDeliveredOrder = Order::where('user_id', $user->id)
        ->where('status', 'delivered')
        ->exists();

    if (!$hasDeliveredOrder) {
        return response()->json([
            'message' => 'Vous devez avoir une commande livrée pour laisser un avis.',
        ], 403);
    }

    // create or update
    $review = Review::updateOrCreate(
        ['user_id' => $user->id],
        [
            'user_name' => $user->name,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]
    );

    return response()->json([
        'message' => 'Avis enregistré avec succès',
        'review' => $review,
    ]);
}



public function destroy($id)
{
    try {
        $review = Review::findOrFail($id);

        $review->delete();

        return response()->json([
            'message' => 'Avis supprimé avec succès',
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Avis introuvable',
        ], 404);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Erreur lors de la suppression de l’avis',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
