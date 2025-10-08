<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderHistory extends Model
{
    protected $table = 'marketplace_order_history';

    protected $fillable = [
        'marketplace_order_id',
        'marketplace_order_item_id',
        'status',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class);
    }

}
