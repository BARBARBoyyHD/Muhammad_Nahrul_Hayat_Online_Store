<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10000, 1000000),
            'flash_sale_price' => null,
        ];
    }

    public function flashSale(?int $quantity = null): static
    {
        return $this->state(fn (array $attributes) => [
            'flash_sale_price' => $attributes['price'] * 0.5,
        ])->afterCreating(function (Product $product) use ($quantity) {
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity' => $quantity ?? 5,
            ]);
        });
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            if (!$product->inventory()->exists()) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                ]);
            }
        });
    }
}
