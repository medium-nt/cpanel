<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperMarketplaceOrderItem
 */
class MarketplaceOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_order_id',
        'marketplace_item_id',
        'storage_barcode',
        'shelf_id',
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

    public function item(): HasOne
    {
        return $this->hasOne(MarketplaceItem::class, 'id', 'marketplace_item_id');
    }

    public function seamstress(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'seamstress_id')->withTrashed();
    }

    public function cutter(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'cutter_id');
    }

    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status];
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    /**
     * @return HasMany<MarketplaceOrderHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(MarketplaceOrderHistory::class);
    }
}
