<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'flash_sale_price' => 'decimal:2',
        ];
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }
}
