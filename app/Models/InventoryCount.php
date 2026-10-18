<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCount extends Model
{
    protected $fillable = [
        'folio',
        'user_id',
        'count_date',
        'description',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'count_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class);
    }
}
