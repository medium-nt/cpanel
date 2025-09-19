<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type_id',
        'height',
        'unit',
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
}
