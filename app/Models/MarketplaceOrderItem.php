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
        'status',
        'seamstress_id',
        'cutter_id',
        'cutting_completed_at',
        'completed_at',
        'created_at',
    ];

    protected $appends = ['status_name', 'status_color'];

    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class);
    }

    public function getStatusColorAttribute(): string
    {
        return StatusMovement::BADGE_COLORS[$this->status];
    }

    public function item()
    {
        return $this->hasOne(MarketplaceItem::class, 'id', 'marketplace_item_id');
    }

    public function seamstress()
    {
        return $this->hasOne(User::class, 'id', 'seamstress_id');
    }

    public function cutter()
    {
        return $this->hasOne(User::class, 'id', 'cutter_id');
    }

    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status];
    }
}
