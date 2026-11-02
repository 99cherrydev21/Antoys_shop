<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $column = DB::selectOne("SHOW COLUMNS FROM inventory_counts LIKE 'status'");

        if (! $column) {
            return;
        }

        $type = (string) $column->Type;

        // Si no es ENUM (por ejemplo VARCHAR), no hay nada que cambiar.
        if (! str_starts_with(strtolower($type), 'enum(')) {
            return;
        }

        // Si ya tiene el valor, tampoco.
        if (str_contains($type, "'aplicado'")) {
            return;
        }

        // Conserva los valores que ya existen y agrega 'aplicado'.
        preg_match_all("/'([^']*)'/", $type, $matches);
        $values = array_merge($matches[1], ['aplicado']);
        $list = implode(',', array_map(fn ($value) => "'{$value}'", $values));

        $nullable = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column->Default !== null ? "DEFAULT '{$column->Default}'" : '';

        DB::statement("ALTER TABLE inventory_counts MODIFY status ENUM({$list}) {$nullable} {$default}");
    }

    public function down(): void
    {
        // No se quita el valor 'aplicado' para no romper filas que ya lo usen.
    }
};
