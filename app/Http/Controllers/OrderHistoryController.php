<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

class OrderHistoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $orders = $request->user()
            ->orders()
            ->with('game')
            ->latest()
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => [
                'data' => $orders->getCollection()->map(fn (Order $order): array => [
                    'invoice' => $order->invoice,
                    'gameName' => $order->game?->name,
                    'productName' => $order->product_name,
                    'target' => $order->target_user_id
                        ? $order->target_user_id.($order->target_zone_id ? ' ('.$order->target_zone_id.')' : '')
                        : null,
                    'total' => Number::currency((float) $order->total, 'IDR', 'id'),
                    'status' => [
                        'value' => $order->status->value,
                        'label' => $order->status->getLabel(),
                        'color' => $order->status->getColor(),
                    ],
                    'createdAt' => $order->created_at?->translatedFormat('d M Y, H:i'),
                    'url' => route('invoice.show', $order),
                ])->values(),
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
                'total' => $orders->total(),
                'prevUrl' => $orders->previousPageUrl(),
                'nextUrl' => $orders->nextPageUrl(),
            ],
        ]);
    }
}
