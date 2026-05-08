<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperMarketplaceSupply
 */
class MarketplaceSupply extends Model
{
    protected $table = 'marketplace_supplies';

    protected $fillable = [
        'supply_id',
        'marketplace_id',
        'type',
        'cluster',
        'supply_date',
        'gazelka_shipment_date',
        'gazelka_shipment_id',
        'status',
        'completed_at',
        'video',
    ];

    protected function casts(): array
    {
        return [
            'supply_date' => 'date',
            'gazelka_shipment_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function getMarketplaceNameAttribute(): string
    {
        return Marketplace::NAME[$this->marketplace_id];
    }

    public function marketplace_orders()
    {
        return $this->hasMany(MarketplaceOrder::class, 'supply_id');
    }
}
