<?php

namespace App\Models;

use Database\Factories\ShiftScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperShiftSchedule
 */
class ShiftSchedule extends Model
{
    /** @use HasFactory<ShiftScheduleFactory> */
    use HasFactory;

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
