<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    use HasFactory;

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
