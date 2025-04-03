<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title'
    ];

    public function movementMaterials() : HasMany
    {
        return $this->hasMany(MovementMaterial::class, 'supplier_id', 'id');
    }
}
