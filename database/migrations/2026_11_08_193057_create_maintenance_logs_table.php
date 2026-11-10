
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_request_id')
                ->nullable()
                ->constrained('maintenance_requests')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Ejemplos: request_created, backup_downloaded,
            // backup_confirmed, reset_approved, reset_completed.
            $table->string('action', 80);

            $table->text('description')->nullable();

            // Información adicional de la operación.
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
