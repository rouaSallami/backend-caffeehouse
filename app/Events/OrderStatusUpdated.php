<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $order;

public function __construct($order)
{
    $this->order = [
        'id' => $order->id,
        'status' => $order->status,
    ];
}

    public function broadcastOn()
    {
        return new Channel('orders');
    }
}