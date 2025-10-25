<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMovementMaterial
 */
class MovementMaterial extends Model
{
    protected $table = 'movement_materials';

    protected $fillable = [
        'material_id',
        'quantity',
        'ordered_quantity',
        'price',
        'order_id'
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

}
