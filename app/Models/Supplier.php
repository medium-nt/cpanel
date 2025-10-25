<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSupplier
 */
class Supplier extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'title',
        'phone',
        'address',
        'comment'
    ];

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class, 'supplier_id', 'id');
    }
}
