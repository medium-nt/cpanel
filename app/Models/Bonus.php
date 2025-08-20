<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    protected $table = 'bonuses';

    protected $fillable = [
        'user_id',
        'title',
        'marketplace_order_item_id',
        'amount',
        'transaction_type',
        'status',
        'paid_at',
        'created_at',
        'updated_at',
    ];
}
