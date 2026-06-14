<?php

namespace App\Rules;

use App\Models\Shift;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Значение поля смены дня календаря: либо «day_off» (выходной),
 * либо id существующей смены, принадлежащей указанному цеху.
 */
readonly class ShiftOrDayOff implements ValidationRule
{
    public function __construct(private int $workshopId) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === 'day_off') {
            return;
        }

        $exists = Shift::query()
            ->where('id', $value)
            ->where('workshop_id', $this->workshopId)
            ->exists();

        if (! $exists) {
            $fail('Выберите смену цеха, «Выходной» или оставьте пустым.');
        }
    }
}
