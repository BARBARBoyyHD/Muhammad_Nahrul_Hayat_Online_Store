<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getTotalAttribute()
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    }
}
