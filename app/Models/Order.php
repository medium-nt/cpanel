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
        'is_approved',
        'completed_at'
    ];

    protected $appends = ['status_name', 'status_color'];

    public function getStatusNameAttribute(): string
    {
        return StatusMovement::STATUSES[$this->status];
    }

    public function getStatusColorAttribute(): string
    {
        return StatusMovement::BADGE_COLORS[$this->status];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    public function movementMaterials(): hasMany
    {
        return $this->hasMany(MovementMaterial::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
