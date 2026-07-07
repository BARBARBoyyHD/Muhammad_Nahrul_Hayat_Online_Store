<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        Product::factory()->create([
            'name' => 'Limited Sneakers',
            'price' => 500000,
        ]);

        Product::factory()->flashSale(5)->create([
            'name' => 'Flash Deal Sneakers',
            'price' => 500000,
        ]);
    }
}
