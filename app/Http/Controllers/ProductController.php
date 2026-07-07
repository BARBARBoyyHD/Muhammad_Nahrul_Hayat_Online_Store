<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('inventory')->get();
        return ProductResource::collection($products);
    }

    public function show($id)
    {
        $product = Product::with('inventory')->find($id);

        if (!$product) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Product not found.',
            ], 404);
        }

        return new ProductResource($product);
    }
}
