<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountItem extends Model
{
    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'stock_expected',
        'quantity_counted',
        'difference',
        'counted_by',
        'counted_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_expected' => 'integer',
            'quantity_counted' => 'integer',
            'difference' => 'integer',
            'counted_at' => 'datetime',
        ];
    }

    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
