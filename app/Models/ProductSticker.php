<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSticker extends Model
{
    protected $fillable = [
        'title',
        'color',
        'print_type',
        'material',
        'country',
        'fastening_type',
    ];
}
