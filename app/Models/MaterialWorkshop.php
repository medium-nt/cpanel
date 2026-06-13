<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMaterialWorkshop
 */
class MaterialWorkshop extends Model
{
    use HasFactory;

    protected $table = 'material_workshop';

    protected $fillable = [
        'material_id',
        'workshop_id',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }
}
