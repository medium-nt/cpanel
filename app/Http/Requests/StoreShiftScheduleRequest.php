<?php

namespace App\Http\Requests;

use App\Rules\ShiftOrDayOff;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workshop_id' => 'required|exists:workshops,id',
            'dates' => 'required|array',
            'dates.*.date' => 'required|date',
            'dates.*.shift_id' => ['nullable', new ShiftOrDayOff((int) $this->input('workshop_id', 1))],
        ];
    }
}
