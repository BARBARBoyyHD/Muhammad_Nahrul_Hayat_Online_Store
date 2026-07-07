<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->unique()->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE inventory ADD CONSTRAINT inventory_quantity_check CHECK (quantity >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
