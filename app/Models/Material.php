<?php

namespace App\Models;

use Database\Factories\MaterialFactory;
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

    /** Максимум рулонов ткани одного вида на смену в цехе */
    public const MAX_FABRIC_ROLLS_PER_SHIFT = 15;

    /** Тип материала: Упаковка */
    public const TYPE_PACKAGING = 3;

    protected $fillable = [
        'title',
        'type_id',
        'height',
        'unit',
        'purchase_price',
        'is_active',
    ];

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

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'material_id', 'id');
    }
}
