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

    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

}
