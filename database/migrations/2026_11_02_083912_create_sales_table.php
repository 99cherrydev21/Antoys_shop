<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->string('folio', 40)->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            $table->string('payment_method', 20);
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);

            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
