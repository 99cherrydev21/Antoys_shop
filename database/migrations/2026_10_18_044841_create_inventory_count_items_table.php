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
    Schema::create('inventory_count_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('inventory_count_id')
            ->constrained('inventory_counts')
            ->cascadeOnDelete();

        $table->foreignId('product_id')
            ->constrained('products')
            ->restrictOnDelete();

        $table->unsignedInteger('stock_expected');

        $table->unsignedInteger('quantity_counted')
            ->nullable();

        $table->integer('difference')->nullable();

        $table->foreignId('counted_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamp('counted_at')->nullable();

        $table->timestamps();

        $table->unique(
            ['inventory_count_id', 'product_id'],
            'inventory_count_product_unique'
        );
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};
