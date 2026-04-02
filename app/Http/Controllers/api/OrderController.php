<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
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
            $totalPrice = 0;

            $order = Order::create([
    'user_id' => $validated['user_id'] ?? null,
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
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
    public function index()
{
    $orders = Order::with(['items.coffee', 'user'])->latest()->get();

    return response()->json($orders);
}
public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|string|max:50',
    ]);

    $order = Order::findOrFail($id);

    $order->update([
        'status' => $validated['status'],
    ]);

    return response()->json([
        'message' => 'Order status updated successfully',
        'order' => $order->load('items.coffee'),
    ]);
}
public function show($id)
{
    $order = Order::with(['items.coffee', 'user'])->findOrFail($id);

    return response()->json($order);
}
public function destroy($id)
{
    $order = Order::findOrFail($id);

    $order->delete();

    return response()->json([
        'message' => 'Order deleted successfully',
    ]);
}
}