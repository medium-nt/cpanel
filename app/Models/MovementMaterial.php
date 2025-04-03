<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementMaterial extends Model
{
    protected $table = 'movement_materials';

    protected $fillable = [
        'material_id',
        'quantity',
        'ordered_quantity',
        'price',
        'comment',
        'type_movement',
        'status_movement',
        'supplier_id',
        'storekeeper_id',
    ];

    protected $appends = ['status_name'];

    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status_movement];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

}
