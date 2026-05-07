<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @mixin IdeHelperShelf
 */
class Roll extends Model
{
    protected $table = 'rolls';

    protected $fillable = [
        'shift_id',
        'roll_code',
        'material_id',
        'status',
        'initial_quantity',
        'shortage_quantity',
        'completed_at',
        'completed_by',
        'is_printed',
    ];

    public const STATUS_IN_STORAGE = 'in_storage';

    public const STATUS_IN_WORKSHOP = 'in_workshop';

    public const STATUS_COMPLETED = 'completed';

    // Список всех статусов
    public static function statuses(): array
    {
        return [
            self::STATUS_IN_STORAGE,
            self::STATUS_IN_WORKSHOP,
            self::STATUS_COMPLETED,
        ];
    }

    /**
     * Заказ поставки от поставщика, через который поступил этот рулон.
     */
    public function supplyOrder(): HasOneThrough
    {
        return $this->hasOneThrough(
            Order::class,
            MovementMaterial::class,
            'roll_id',
            'id',
            'id',
            'order_id',
        )->where('orders.type_movement', 1);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Сотрудник, закрывший рулон.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function movementMaterialsNotFromSuppler(): HasMany
    {
        return $this->hasMany(MovementMaterial::class)
            ->whereRelation('order', 'type_movement', '!=', 1);
    }

    public function movementMaterial(): HasOne
    {
        return $this->hasOne(MovementMaterial::class);
    }

    public function getStatusNameAttribute(): string
    {
        $map = [
            self::STATUS_IN_STORAGE => 'На складе',
            self::STATUS_IN_WORKSHOP => 'В цехе',
            self::STATUS_COMPLETED => 'Завершен',
        ];

        return $map[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $map = [
            self::STATUS_IN_STORAGE => 'badge-primary',
            self::STATUS_IN_WORKSHOP => 'badge-warning',
            self::STATUS_COMPLETED => 'badge-secondary',
        ];

        return $map[$this->status] ?? 'badge-danger';
    }

    public function getCurrentQuantityAttribute(): float
    {
        $used = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('roll_id', $this->id)
            ->whereIn('orders.type_movement', [3, 4])
            ->sum('quantity');

        return round($this->initial_quantity - $used, 3);
    }
}
