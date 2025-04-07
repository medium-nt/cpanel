<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceItem extends Model
{
    protected $fillable = [
        'title',
        'sku',
        'width',
        'height',
        'marketplace_id',
    ];

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class, 'marketplace_order_id', 'id');
    }
}
