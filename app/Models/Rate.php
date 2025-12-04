<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperRate
 */
class Rate extends Model
{
    use HasFactory;

    protected $table = 'rates';

    protected $fillable = [
        'user_id',
        'material_id',
        'rate',
        'not_cutter_rate',
        'cutter_rate',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id', 'id');
    }
}
