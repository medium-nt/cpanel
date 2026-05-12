<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyBox extends Model
{
    protected $fillable = [
        'marketplace_supply_id',
        'number',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (SupplyBox $box) {
            $datePart = now()->format('dmy');
            $idPart = str_pad((string) $box->id, 7, '0', STR_PAD_LEFT);
            $box->update(['number' => 'FBO-WB_'.$datePart.$idPart]);
        });
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSupply::class, 'marketplace_supply_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'box_id');
    }
}
