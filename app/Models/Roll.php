<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperShelf
 */
class Roll extends Model
{
    protected $table = 'rolls';

    protected $fillable = [
        'roll_code',
        'material_id',
        'status',
        'initial_quantity',
        'shortage_quantity',
        'completed_at',
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

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function movementMaterials(): HasMany
    {
        return $this->hasMany(MovementMaterial::class)
            ->whereRelation('order', 'type_movement', '!=', 1);
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

    public function getCurrentQuantityAttribute(): int
    {
        $used = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('roll_id', $this->id)
            ->whereIn('orders.type_movement', [3, 4])
            ->sum('quantity');

        return $this->initial_quantity - $used;
    }
}
