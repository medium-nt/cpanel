<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrder extends Model
{
    protected $table = 'marketplace_orders';

    protected $fillable = [
        'marketplace_id',
        'order_id',
        'status',
        'fulfillment_type',
        'completed_at',
        'created_at'
    ];

    protected $appends = ['marketplace_name', 'status_name', 'status_color'];

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    public function getMarketplaceNameAttribute(): string
    {
        return Marketplace::NAME[$this->marketplace_id];
    }

    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status];
    }

    public function getStatusColorAttribute(): string
    {
        return StatusMovement::BADGE_COLORS[$this->status];
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSupply::class);
    }

}
