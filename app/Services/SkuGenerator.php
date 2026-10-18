<?php

namespace App\Services;

use App\Models\SkuCounter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SkuGenerator
{
    public function next(string $prefix = 'ANT'): string
    {
        return DB::transaction(function () use ($prefix) {
            // Creamos el contador si todavía no existe.
            DB::table('sku_counters')->insertOrIgnore([
                'prefix' => $prefix,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Bloqueamos el contador para evitar duplicados simultáneos.
            $counter = SkuCounter::where('prefix', $prefix)
                ->lockForUpdate()
                ->firstOrFail();

            $counter->last_number++;
            $counter->save();

            if ($counter->last_number > 9999999) {
                throw new RuntimeException(
                    'Se agotó la secuencia de SKU para el prefijo ' . $prefix
                );
            }

            return $prefix . '-' . str_pad(
                (string) $counter->last_number,
                7,
                '0',
                STR_PAD_LEFT
            );
        }, 3);
    }
}
