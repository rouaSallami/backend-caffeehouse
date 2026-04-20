<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\OrderStatusUpdated;
use Twilio\Rest\Client;

class OrderController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'customer_name' => 'required|string|max:255',
        'customer_phone' => 'nullable|string|max:30',
        'mode' => 'required|string|max:50',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.coffee_id' => 'required|exists:coffees,id',
        'items.*.size_name' => 'required|string|max:50',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.sugar' => 'nullable|integer|min:0|max:100',
        'items.*.container' => 'nullable|string|max:50',
        'items.*.note' => 'nullable|string',
        'items.*.milk' => 'nullable|string|max:50',
        'items.*.addons' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {
        $subtotalPrice = 0;

        $order = Order::create([
            'user_id' => $request->user()->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'mode' => $request->input('mode'),
            'notes' => $validated['notes'] ?? null,
            'subtotal_price' => 0,
            'discount_amount' => 0,
            'applied_promo_code' => null,
            'total_price' => 0,
            'status' => 'pending',
        ]);

        foreach ($validated['items'] as $item) {
            $subtotal = $item['unit_price'] * $item['quantity'];
            $subtotalPrice += $subtotal;

            OrderItem::create([
                'order_id' => $order->id,
                'coffee_id' => $item['coffee_id'],
                'size_name' => $item['size_name'],
                'sugar' => $item['sugar'] ?? 0,
                'container' => $item['container'] ?? null,
                'milk' => $item['milk'] ?? null,
                'note' => $item['note'] ?? null,
                'addons' => $item['addons'] ?? [],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
            ]);
        }

        $userPromotion = $this->getActiveWelcomePromotionForUser($request->user());
        $discountAmount = $this->calculatePromotionDiscount($userPromotion, (float) $subtotalPrice);
        $finalTotal = max(round($subtotalPrice - $discountAmount, 2), 0);

        $order->update([
            'subtotal_price' => $subtotalPrice,
            'discount_amount' => $discountAmount,
            'applied_promo_code' => $discountAmount > 0 && $userPromotion?->promotion
                ? $userPromotion->promotion->code
                : null,
            'total_price' => $finalTotal,
        ]);

        if ($discountAmount > 0 && $userPromotion) {
            $userPromotion->update([
                'used_at' => now(),
                'order_id' => $order->id,
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('items.coffee'),
        ], 201);
    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Failed to create order',
            'error' => $e->getMessage(),
        ], 500);
    }
}
   public function index(Request $request)
{
    $user = $request->user();

    if ($user->role === 'admin') {
        $orders = Order::with(['items.coffee', 'user'])
            ->where('is_archived', false) // 🔥 هذا هو الحل
            ->latest()
            ->get();
    } else {
        $orders = Order::with(['items.coffee'])
            ->where('user_id', $user->id)
            ->where('is_archived', false) // 🔥 نفس الشي user
            ->latest()
            ->get();
    }

    return response()->json($orders);
}
public function updateStatus(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,out_for_delivery,delivered,cancelled',
        ]);

        $order = Order::with('items.coffee')->findOrFail($id);

       $newStatus = $validated['status'];

$finalStatuses = ['delivered', 'cancelled'];
$isCompleted = in_array($newStatus, $finalStatuses, true);

$order->update([
    'status' => $newStatus,
    'completed_at' => $isCompleted ? now() : null,
    'is_archived' => $isCompleted,
]);


$this->awardLoyaltyPointsIfEligible($order);
$order->refresh();

        if (
    ($order->status === 'out_for_delivery' && $order->mode === 'livraison') ||
    ($order->status === 'ready' && $order->mode === 'emporter')
) {
    \Log::info('Status is ready, sending SMS', [
        'order_id' => $order->id,
        'status' => $order->status,
        'mode' => $order->mode,
    ]);

    $this->sendSmsNotification($order);
}

        event(new OrderStatusUpdated($order));

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Statut invalide',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Commande introuvable',
        ], 404);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Erreur lors de la mise à jour du statut',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function show(Request $request, $id)
{
    $order = Order::with(['items.coffee', 'user'])->findOrFail($id);

    if (
        $request->user()->role !== 'admin' &&
        $order->user_id !== $request->user()->id
    ) {
        return response()->json([
            'message' => 'Accès interdit',
        ], 403);
    }

    return response()->json($order);
}



public function cancel(Request $request, $id)
{
    try {
        $order = Order::findOrFail($id);

        
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Accès interdit',
            ], 403);
        }

        
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Cette commande ne peut plus être annulée',
            ], 422);
        }

        $order->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'is_archived' => true,
        ]);

        event(new OrderStatusUpdated($order));

        return response()->json([
            'message' => 'Commande annulée avec succès',
            'order' => $order,
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'message' => 'Commande introuvable',
        ], 404);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Erreur lors de l’annulation de la commande',
            'error' => $e->getMessage(),
        ], 500);
    }
}




public function destroy(Request $request, $id)
{
    $order = Order::findOrFail($id);

    if ($request->user()->role !== 'admin' && $order->user_id !== $request->user()->id) {
        return response()->json([
            'message' => 'Accès interdit'
        ], 403);
    }

    $order->delete();

    return response()->json([
        'message' => 'Order deleted successfully',
    ], 200);
}

private function sendSmsNotification($order)
{
    \Log::info('SMS function started', [
        'order_id' => $order->id,
        'status' => $order->status,
        'customer_phone_raw' => $order->customer_phone,
    ]);

    $phone = $order->customer_phone;

    if (!$phone) {
        \Log::warning('SMS skipped: no phone number', [
            'order_id' => $order->id,
        ]);
        return;
    }

    if (!str_starts_with($phone, '+')) {
        $phone = '+216' . ltrim($phone, '0');
    }

    $customerName = $order->customer_name ?: 'Client';
    $mode = $order->mode ?: 'commande';
    $total = number_format((float) $order->total_price, 2, '.', '');
    $heure = now()->format('H:i');

    // إذا تحب label أحسن للmode
    $modeLabel = match ($mode) {
        'livraison' => 'livraison',
        'emporter' => 'a emporter',
        'surplace' => 'sur place',
        default => $mode,
    };

    $message = "CoffeeHouse: Bonjour {$customerName}, votre commande #{$order->id} ({$modeLabel}) est en livraison. Total: {$total} DT. Heure: {$heure}.";

    try {
        $client = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));

        $result = $client->messages->create($phone, [
            'from' => env('TWILIO_FROM'),
            'body' => $message,
        ]);

        \Log::info('SMS sent successfully', [
            'order_id' => $order->id,
            'sid' => $result->sid ?? null,
        ]);
    } catch (\Throwable $e) {
        \Log::error('SMS error full', [
            'order_id' => $order->id,
            'message' => $e->getMessage(),
        ]);
    }
} 



private function awardLoyaltyPointsIfEligible(Order $order): void
{
    if (!$order->user_id) {
        return;
    }

    $isEligibleStatus =
        ($order->mode === 'livraison' && $order->status === 'delivered') ||
        ($order->mode === 'emporter' && $order->status === 'delivered') ||
        ($order->mode === 'surplace' && $order->status === 'delivered');

    if (!$isEligibleStatus) {
        return;
    }

    if ($order->loyalty_points_awarded_at) {
        return;
    }

    $pointsEarned = (int) floor((float) $order->total_price);

    if ($pointsEarned <= 0) {
        $order->update([
            'loyalty_points_awarded_at' => now(),
        ]);
        return;
    }

    DB::transaction(function () use ($order, $pointsEarned) {
        $order->refresh();

        if ($order->loyalty_points_awarded_at) {
            return;
        }

        $user = $order->user()->lockForUpdate()->first();

        if (!$user) {
            return;
        }

        $user->increment('points', $pointsEarned);

        $order->update([
            'loyalty_points_awarded_at' => now(),
        ]);
    });
}


private function getActiveWelcomePromotionForUser($user)
{
    $welcomeCode = config('promotions.welcome.code', 'WELCOME25');

    return $user->userPromotions()
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
}



private function calculatePromotionDiscount($userPromotion, float $subtotal): float
{
    if (!$userPromotion || !$userPromotion->promotion) {
        return 0;
    }

    $promotion = $userPromotion->promotion;

    if (!$promotion->is_active) {
        return 0;
    }

    if ($subtotal <= 0) {
        return 0;
    }

    $discount = 0;

    if ($promotion->type === 'percentage') {
        $discount = $subtotal * ((float) $promotion->value / 100);
    } elseif ($promotion->type === 'fixed') {
        $discount = (float) $promotion->value;
    }

    if ($promotion->max_discount !== null) {
        $discount = min($discount, (float) $promotion->max_discount);
    }

    $discount = min($discount, $subtotal);

    return round($discount, 2);
}


}