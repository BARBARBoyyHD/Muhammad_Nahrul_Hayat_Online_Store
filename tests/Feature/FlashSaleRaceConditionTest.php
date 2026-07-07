<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Requires the dev server to be running on port 8000.
 * Start with: php artisan serve --port=8000
 */
class FlashSaleRaceConditionTest extends TestCase
{
    private string $baseUrl = 'http://127.0.0.1:8000';

    public function test_flash_sale_race_condition()
    {
        Product::factory()->flashSale(5)->create([
            'name' => 'Flash Deal Sneakers',
            'price' => 500000,
        ]);

        $responses = Http::pool(fn (Pool $pool) => array_fill(0, 15, $pool->post("{$this->baseUrl}/api/orders", [
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ])));

        $success = collect($responses)->filter(fn ($r) => $r !== null && $r->status() === 201)->count();
        $failures = collect($responses)->filter(fn ($r) => $r !== null && $r->status() === 409)->count();

        $this->assertEquals(5, $success, "Expected 5 successful orders, got {$success}");
        $this->assertEquals(10, $failures, "Expected 10 rejected orders, got {$failures}");

        $this->assertDatabaseHas('inventory', [
            'product_id' => 1,
            'quantity' => 0,
        ]);
    }
}
