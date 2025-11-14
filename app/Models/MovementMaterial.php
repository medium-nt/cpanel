<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMovementMaterial
 *
 * @property int $quantity
 * @property Material $material
 */
class MovementMaterial extends Model
{
    use HasFactory;

    protected $table = 'movement_materials';

    protected $fillable = [
        'material_id',
        'quantity',
        'ordered_quantity',
        'price',
        'order_id',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
