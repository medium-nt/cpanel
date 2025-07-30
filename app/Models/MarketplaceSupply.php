<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSupply extends Model
{
    protected $table = 'marketplace_supplies';

    protected $fillable = [
        'supply_id',
        'marketplace_id',
        'status',
        'completed_at',
        'video',
    ];

    public function getMarketplaceNameAttribute(): string
    {
        return Marketplace::NAME[$this->marketplace_id];
    }

    public function marketplace_orders()
    {
        return $this->hasMany(MarketplaceOrder::class , 'supply_id');
    }

}
