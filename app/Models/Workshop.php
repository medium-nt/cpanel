<?php

namespace App\Models;

use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'title',
        'status',
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function marketplaceOrderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function allowedItems(): BelongsToMany
    {
        return $this->belongsToMany(MarketplaceItem::class, 'item_workshop')
            ->withTimestamps();
    }

    /**
     * Материалы, доступные для заказа в этом цехе.
     */
    public function allowedMaterials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_workshop')
            ->withTimestamps();
    }

    /** Фильтрует запрос по активным цехам. */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
