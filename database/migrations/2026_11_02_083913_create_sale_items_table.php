<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones.
     */
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            // Venta a la que pertenece este detalle.
            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            // Producto vendido.
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // Copia de los datos del producto al momento de la venta.
            $table->string('product_name', 150);
            $table->string('product_sku', 64);

            // Cantidad vendida y precio aplicado (ya agregados).
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();

            // Índice para consultar rápidamente los detalles de una venta.
            $table->index(['sale_id', 'product_id']);
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
