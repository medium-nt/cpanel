<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperHanger
 */
class Hanger extends Model
{
    use HasFactory;

    protected $table = 'hangers';

    protected $fillable = [
        'title',
    ];

    /** Товары, привязанные к вешалке при сдаче кроя. */
    public function orderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    /** Закройщики, у которых эта вешалка выбрана текущей в профиле. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
