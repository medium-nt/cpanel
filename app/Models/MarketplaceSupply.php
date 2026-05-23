<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperMarketplaceSupply
 */
class MarketplaceSupply extends Model
{
    public const DELIVERY_TYPE_BOX = 'короба';

    public const DELIVERY_TYPE_PALLET = 'палета';

    public const DELIVERY_TYPES = [
        self::DELIVERY_TYPE_BOX,
        self::DELIVERY_TYPE_PALLET,
    ];

    protected $table = 'marketplace_supplies';

    protected $fillable = [
        'supply_id',
        'marketplace_id',
        'type',
        'cluster',
        'supply_date',
        'gazelka_shipment_date',
        'gazelka_shipment_id',
        'delivery_type',
        'gazelka_pickup',
        'status',
        'completed_at',
        'video',
        'sticker',
    ];

    protected function casts(): array
    {
        return [
            'supply_date' => 'date',
            'gazelka_shipment_date' => 'date',
            'gazelka_pickup' => 'boolean',
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
