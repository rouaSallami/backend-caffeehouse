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
            'customer_phone' => 'required|string|max:30',
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
            $totalPrice = 0;

            $order = Order::create([
    'user_id' => $request->user()->id,
    'customer_name' => $validated['customer_name'],
    'customer_phone' => $validated['customer_phone'] ?? null,
    'mode' => $request->input('mode'),
    'notes' => $validated['notes'] ?? null,
    'total_price' => 0,
    'status' => 'pending',
]);

            foreach ($validated['items'] as $item) {
                $subtotal = $item['unit_price'] * $item['quantity'];
                $totalPrice += $subtotal;

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

            $order->update([
                'total_price' => $totalPrice,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('items.coffee'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
    'message' => 'Failed to create order',
], 500);
        }
    }
    public function index(Request $request)
{
    $user = $request->user();

    if ($user->role === 'admin') {
        $orders = Order::with(['items.coffee', 'user'])->latest()->get();
    } else {
        $orders = Order::with(['items.coffee'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    return response()->json($orders);
}
public function updateStatus(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled',
        ]);

        $order = Order::with('items.coffee')->findOrFail($id);

        $order->update([
            'status' => $validated['status'],
        ]);

        if (
    $order->status === 'ready' ||
    $order->status === 'Prête à être servie'
) {
            if ($order->status === 'ready') {
    \Log::info('Status is ready, sending SMS', [
        'order_id' => $order->id,
        'status' => $order->status,
    ]);

    $this->sendSmsNotification($order);
}
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

    \Log::info('SMS normalized phone', [
        'order_id' => $order->id,
        'phone' => $phone,
        'from' => env('TWILIO_FROM'),
        'sid_exists' => !empty(env('TWILIO_SID')),
        'token_exists' => !empty(env('TWILIO_TOKEN')),
    ]);

    $message = "CoffeeHouse: votre commande #{$order->id} est prete.";

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
}