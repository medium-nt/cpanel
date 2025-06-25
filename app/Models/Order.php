<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'type_movement',
        'status',
        'supplier_id',
        'storekeeper_id',
        'seamstress_id',
        'comment',
        'marketplace_order_id',
        'is_approved',
        'completed_at'
    ];

    protected $appends = ['status_name', 'status_color', 'type_movement_name'];

    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status];
    }

    public function getStatusColorAttribute(): string
    {
        return StatusMovement::BADGE_COLORS[$this->status];
    }

    public function getTypeMovementNameAttribute(): string
    {
        return TypeMovement::TYPES[$this->type_movement];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    public function seamstress(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seamstress_id');
    }

    public function movementMaterials(): hasMany
    {
        return $this->hasMany(MovementMaterial::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getUpdatedDateAttribute()
    {
        return $this->updated_at->format('d/m/Y');
    }

    public function getCreatedDateAttribute()
    {
        return $this->created_at->format('d/m/Y');
    }
}
