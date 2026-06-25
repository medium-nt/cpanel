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
        'workshop_id',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Цех, к которому относится запись календаря смен.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /** Проверяет, является ли день выходным (смена не назначена). */
    public function isDayOff(): bool
    {
        return $this->shift_id === null;
    }
}
