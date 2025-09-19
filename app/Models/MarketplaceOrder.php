<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrder extends Model
{
    use HasFactory;

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

    public function getMarketplaceStatusLabelAttribute(): string
    {
        return match ($this->marketplace_status) {
//            'acceptance_in_progress' => '<span class="badge bg-secondary">идёт приёмка</span>',
//            'awaiting_approve' => '<span class="badge bg-secondary">ожидает подтверждения</span>',
//            'awaiting_packaging' => '<span class="badge bg-secondary">ожидает упаковки</span>',
            'awaiting_deliver' => '<span class="badge bg-secondary">ожидает отгрузки</span>',
            'confirm' => '<span class="badge bg-secondary">на сборке</span>',
            'complete', 'delivering' => '<span class="badge bg-success">в доставке</span>',
            'delivered' => '<span class="badge bg-success">доставлено</span>',
            'cancel', 'cancelled', => '<span class="badge bg-danger">отменено</span>',
            default => $this->marketplace_status ?? '',
        };
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
