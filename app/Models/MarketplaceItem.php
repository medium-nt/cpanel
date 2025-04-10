<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceItem extends Model
{
    protected $fillable = [
        'title',
        'width',
        'height',
    ];

    public function marketplaceOrderItem(): HasMany
    {
        return $this->HasMany(MarketplaceOrderItem::class, 'marketplace_item_id', 'id');

    }

    public function sku(): HasMany
    {
        return $this->hasMany(Sku::class, 'item_id', 'id');
    }

    public function consumption(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class, 'item_id', 'id');
    }
}
