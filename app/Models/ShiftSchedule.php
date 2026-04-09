<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperShiftSchedule
 */
class ShiftSchedule extends Model
{
    protected $table = 'shift_schedule';

    protected $fillable = [
        'shift_id',
        'date',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
