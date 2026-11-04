<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'sale_id')) {
                $table->foreignId('sale_id')
                    ->constrained('sales')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('sale_items', 'product_id')) {
                $table->foreignId('product_id')
                    ->constrained('products')
                    ->restrictOnDelete();
            }

            if (!Schema::hasColumn('sale_items', 'product_name')) {
                $table->string('product_name', 150)->nullable();
            }

            if (!Schema::hasColumn('sale_items', 'product_sku')) {
                $table->string('product_sku', 64)->nullable();
            }

            if (!Schema::hasColumn('sale_items', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1);
            }

            if (!Schema::hasColumn('sale_items', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->default(0);
            }

            if (!Schema::hasColumn('sale_items', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0);
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(
                ['sale_id', 'product_id'],
                'sale_items_sale_product_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_sale_product_index');
        });
    }
};
