<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceItem extends Model
{
    protected $fillable = [
        'title',
        'sku',
        'width',
        'height',
        'marketplace_id',
    ];

    protected $appends = ['marketplace_name'];

    public function marketplaceOrderItem(): HasMany
    {
        return $this->HasMany(MarketplaceOrderItem::class, 'marketplace_item_id', 'id');

    }

    public function getMarketplaceNameAttribute(): string
    {
        return Marketplace::NAME[$this->marketplace_id];
    }
}
