<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sku extends Model
{
    protected $table = 'skus';

    protected $fillable = [
        'item_id',
        'sku',
        'marketplace_id'
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class);
    }
}
