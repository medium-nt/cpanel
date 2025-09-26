<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    protected $table = 'shelves';

    protected $fillable = [
        'title'
    ];

    public function orderItems()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }
}
