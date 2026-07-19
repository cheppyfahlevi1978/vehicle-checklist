<?php

namespace App\Services;

use App\Models\Order;
use DomainException;

class OrderWorkflow
{
    private const TRANSITIONS = [
        'PLACED' => ['ACCEPTED', 'CANCELLED'],
        'ACCEPTED' => ['PREPARING', 'CANCELLED'],
        'PREPARING' => ['READY_FOR_PICKUP'],
        'READY_FOR_PICKUP' => ['PICKED_UP'],
        'PICKED_UP' => ['DELIVERED'],
        'DELIVERED' => [],
        'CANCELLED' => [],
    ];

    public function transition(Order $order, string $next): Order
    {
        $next = strtoupper($next);
        if (! in_array($next, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw new DomainException("Perubahan status {$order->status} ke {$next} tidak diizinkan.");
        }
        $order->update(['status' => $next]);
        return $order->fresh();
    }
}
