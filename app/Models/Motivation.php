<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperMotivation
 */
class Motivation extends Model
{
    use HasFactory;

    protected $table = 'motivations';

    protected $fillable = [
        'user_id',
        'from',
        'to',
        'rate',
        'bonus',
        'not_cutter_bonus',
        'cutter_bonus',
    ];
}
