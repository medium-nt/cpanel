<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motivation extends Model
{
    protected $table = 'motivations';

    protected $fillable = [
        'user_id',
        'from',
        'to',
        'rate',
        'bonus',
    ];
}
