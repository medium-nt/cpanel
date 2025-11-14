<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperTypeMaterial
 */
class TypeMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];
}
