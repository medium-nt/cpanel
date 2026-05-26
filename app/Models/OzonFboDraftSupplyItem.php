<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OzonFboDraftSupplyItem extends Model
{
    protected $table = 'ozon_fbo_draft_supply_items';

    protected $fillable = [
        'supply_id',
        'sku',
        'quantity',
    ];

    /**
     * Связь с поставкой.
     */
    public function supply(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSupply::class, 'supply_id');
    }

    /**
     * Связь с SKU записью (по полю sku, не по foreign key).
     */
    public function skuRecord(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku', 'sku');
    }
}
