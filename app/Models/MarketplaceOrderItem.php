<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderItem extends Model
{
    protected $fillable = [
        'marketplace_order_id',
        'marketplace_item_id',
        'quantity',
        'price',
    ];

    public function marketplaceOrder()
    {
        return $this->belongsTo(MarketplaceOrder::class);
    }

    public function item()
    {
        return $this->hasOne(MarketplaceItem::class, 'id', 'marketplace_item_id');
    }
}
