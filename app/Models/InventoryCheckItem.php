<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperOrder
 */
class InventoryCheckItem extends Model
{
//    use HasFactory;

    protected $table = 'inventory_check_items';

    protected $fillable = [
        'inventory_check_id',
        'marketplace_order_item_id',
        'expected_shelf_id',
        'founded_shelf_id',
        'is_found',
        'is_added_later',
    ];

    public function expectedShelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'expected_shelf_id');
    }

    public function foundedShelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'founded_shelf_id');
    }

    public function marketplaceOrderItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class, 'marketplace_order_item_id');
    }

}
