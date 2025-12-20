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

    protected $fillable = [
        'title',
        'type_id',
        'height',
        'unit',
        'purchase_price',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeMaterial::class);
    }

    public function movementMaterials(): HasMany
    {
        return $this->hasMany(MovementMaterial::class);
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
