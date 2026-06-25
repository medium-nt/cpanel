<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperOrder
 *
 * @property 'in_progress'|'closed' $status
 */
class InventoryCheck extends Model
{
    //    use HasFactory;

    protected $table = 'inventory_checks';

    protected $fillable = [
        'status',
        'comment',
        'finished_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
        'status' => 'string',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCheckItem::class);
    }

    /** Дата создания инвентаризации в формате d/m/Y H:i:s. */
    public function getCreatedDateAttribute(): ?string
    {
        return $this->created_at?->format('d/m/Y H:i:s');
    }

    /** Дата завершения инвентаризации в формате d/m/Y H:i:s. */
    public function getFinishedDateAttribute(): ?string
    {
        return $this->finished_at?->format('d/m/Y H:i:s');
    }
}
