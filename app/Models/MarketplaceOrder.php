<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    protected $table = 'marketplace_orders';

    protected $fillable = [
        'marketplace_id',
        'order_id',
        'status'
    ];

    protected $appends = ['marketplace_name'];

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    public function getMarketplaceNameAttribute(): string
    {
        return Marketplace::NAME[$this->marketplace_id];
    }

}
