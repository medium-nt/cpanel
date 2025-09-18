<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'name',
        'value',
    ];

    public static function getValue(string $name): ?string
    {
        return static::query()
            ->where('name', $name)
            ->value('value');
    }

}
