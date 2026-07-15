<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $status
 * @property bool $is_b2b Признак заказа от юридического лица (B2B)
 * @property Carbon|null $boxed_at
 * @property-read Collection|MarketplaceOrderItem[] $items
 * @property MarketplaceSupply|null $supply
 *
 * @mixin IdeHelperMarketplaceOrder
 */
class MarketplaceOrder extends Model
{
    use HasFactory;

    protected $table = 'marketplace_orders';

    protected $fillable = [
        'marketplace_id',
        'order_id',
        'supply_id',
        'box_id',
        'boxed_at',
        'status',
        'fulfillment_type',
        'completed_at',
        'created_at',
        'returned_at',
        'cluster',
        'is_b2b',
    ];

    protected $appends = ['marketplace_name', 'status_name', 'status_color'];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'boxed_at' => 'datetime',
            'is_b2b' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    /** Возвращает иконку маркетплейса по ID (OZON/WB). */
    public function getMarketplaceNameAttribute(): string
    {
        return Marketplace::NAME[$this->marketplace_id];
    }

    /** Возвращает заголовок маркетплейса (OZON/WB/---). */
    public function getMarketplaceTitleAttribute(): string
    {
        return match ($this->marketplace_id) {
            1 => 'OZON',
            2 => 'WB',
            default => '---',
        };
    }

    /** Возвращает текстовое описание статуса заказа. */
    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status];
    }

    /** Возвращает HTML-бейдж со статусом на стороне маркетплейса. */
    public function getMarketplaceStatusLabelAttribute(): string
    {
        return match ($this->marketplace_status) {
            'awaiting_deliver' => '<span class="badge bg-secondary">ожидает отгрузки</span>',
            'confirm' => '<span class="badge bg-secondary">на сборке</span>',
            'complete', 'delivering' => '<span class="badge bg-success">в доставке</span>',
            'delivered' => '<span class="badge bg-success">доставлено</span>',
            'cancel', 'cancelled', => '<span class="badge bg-danger">отменено</span>',
            default => $this->marketplace_status ?? '',
        };
    }

    /** Возвращает цвет бейджа для текущего статуса. */
    public function getStatusColorAttribute(): string
    {
        return StatusMovement::BADGE_COLORS[$this->status];
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSupply::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(SupplyBox::class, 'box_id');
    }

    /** Возвращает дату завершения в формате d/m/Y. */
    public function getCompletedDateAttribute(): string
    {
        return Carbon::parse($this->completed_at)->format('d/m/Y');
    }

    /** Возвращает дату возврата в формате d/m/Y или пустую строку. */
    public function getReturnedDateAttribute(): string
    {
        return $this->returned_at ? Carbon::parse($this->returned_at)->format('d/m/Y') : '';
    }

    public function history(): HasOne
    {
        return $this->hasOne(MarketplaceOrderHistory::class);
    }

    /** Проверяет, находится ли заказ на этапе наклейки (статус 5). */
    public function isStickering(): bool
    {
        return $this->status == 5;
    }
}
