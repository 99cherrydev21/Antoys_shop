<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }


    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
    public function movements()
    {
        return $this->hasMany(InventoryMovement::class)
            ->latest();
    }
}
