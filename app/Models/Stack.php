<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperStack
 */
class Stack extends Model
{
    use HasFactory;

    protected $fillable = [
        'seamstress_id',
        'stack',
        'max',
    ];

    //
}
