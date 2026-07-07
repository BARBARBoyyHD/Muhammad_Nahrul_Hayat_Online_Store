<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function store(PlaceOrderRequest $request)
    {
        $order = $this->orderService->placeOrder($request->input('items'));
        return new OrderResource($order);
    }

    public function index()
    {
        $orders = Order::with('items.product')->get();
        return OrderResource::collection($orders);
    }

    public function show($id)
    {
        $order = Order::with('items.product')->find($id);

        if (!$order) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Order not found.',
            ], 404);
        }

        return new OrderResource($order);
    }
}
