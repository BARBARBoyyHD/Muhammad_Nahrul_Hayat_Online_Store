<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function placeOrder(array $items): Order
    {
        return DB::transaction(function () use ($items) {
            $order = Order::create();

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                $inventory = Inventory::where('product_id', $product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($inventory->quantity < $item['quantity']) {
                    abort(response()->json([
                        'error' => 'insufficient_stock',
                        'message' => "Product '{$product->name}' only has {$inventory->quantity} units remaining.",
                    ], 409));
                }

                $inventory->decrement('quantity', $item['quantity']);

                $unitPrice = $product->flash_sale_price ?? $product->price;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                ]);
            }

            return $order->fresh()->load('items.product');
        });
    }
}
