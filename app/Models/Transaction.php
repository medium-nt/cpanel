<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTransaction
 * @property string $user_name
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'marketplace_order_item_id',
        'accrual_for_date',
        'amount',
        'status',
        'transaction_type',
        'paid_at',
        'is_bonus',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
