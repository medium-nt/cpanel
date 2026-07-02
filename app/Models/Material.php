<?php

namespace App\Models;

use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperMaterial
 */
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    use SoftDeletes;

    /** Тип материала: Ткань */
    public const TYPE_FABRIC = 1;

    /** Тип материала: Аксессуары (тесьма и т.п.) */
    public const TYPE_ACCESSORY = 2;

    /** Тип материала: Упаковка */
    public const TYPE_PACKAGING = 3;

    protected $fillable = [
        'title',
        'type_id',
        'height',
        'unit',
        'purchase_price',
        'is_active',
        'is_archive',
        'minimum_roll_size_for_closure',
    ];

    /**
     * Приведение типов атрибутов материала.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_roll_size_for_closure' => 'decimal:2',
        ];
    }

    /**
     * Скоуп: только не архивированные материалы (видимы в просмотрах остатков).
     *
     * @param  Builder<self>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->where('is_archive', false);
    }

    /**
     * Скоуп: материалы, доступные для заказа/выбора в формах (активные и не в архиве).
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->where('is_archive', false);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeMaterial::class);
    }

    public function rolls(): HasMany
    {
        return $this->hasMany(Roll::class);
    }

    public function movementMaterials(): HasMany
    {
        return $this->hasMany(MovementMaterial::class);
    }

    /**
     * Цеха, в которых доступен данный материал.
     */
    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'material_workshop')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Поставщики материала с процентом недостачи.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'material_supplier')
            ->withPivot('id', 'shortage_percent')
            ->withTimestamps()
            ->orderBy('suppliers.title');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'material_id', 'id');
    }
}
