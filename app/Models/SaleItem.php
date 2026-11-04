<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /**
     * Campos que se pueden asignar mediante create() o fill().
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'original_price',
        'subtotal',
    ];

    /**
     * Conversión de tipos de los atributos.
     */
    protected function casts(): array
    {
        return [
            'sale_id'    => 'integer',
            'product_id' => 'integer',
            'quantity'   => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal'   => 'decimal:2',
        ];
    }

    /**
     * Relación: el detalle pertenece a una venta.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relación: el detalle corresponde a un producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

