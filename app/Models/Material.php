<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'type_id',
        'height',
        'unit',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeMaterial::class);
    }
}
