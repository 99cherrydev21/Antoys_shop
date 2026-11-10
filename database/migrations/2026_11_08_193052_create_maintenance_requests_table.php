
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            // Usuario jefe que solicita el restablecimiento.
            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();

            // Técnico que atiende la solicitud.
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            // Tipo de mantenimiento.
            $table->string('type', 50)->default('system_reset');

            // Estado del proceso.
            $table->string('status', 40)->default('pending_backup');

            // Información del respaldo.
            $table->string('backup_filename')->nullable();
            $table->string('backup_checksum', 64)->nullable();
            $table->unsignedBigInteger('backup_size')->nullable();
            $table->timestamp('backup_confirmed_at')->nullable();

            // Autorización del jefe.
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')->nullable();

            // Ejecución del restablecimiento.
            $table->foreignId('executed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Resultado y observaciones.
            $table->text('description')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
