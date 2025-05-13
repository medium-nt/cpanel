<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'working_day_start' => 'required|date_format:H:i',
            'working_day_end' => 'required|date_format:H:i',
            'is_enabled_work_schedule' => 'required|in:0,1',
            'api_key_wb' => 'sometimes|nullable|string',
            'api_key_ozon' => 'sometimes|nullable|string',
            'seller_id_ozon' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Customize the error messages used by the validator.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'working_day_start.date_format' => 'Неверный формат времени начала рабочего дня',
            'working_day_start.required' => 'Поле "Начало рабочего дня" обязательно для заполнения',

            'working_day_end.date_format' => 'Неверный формат времени конца рабочего дня',
            'working_day_end.required' => 'Поле "Конец рабочего дня" обязательно для заполнения',

            'is_enabled_work_schedule.required' => 'Поле "Включен ли рабочий график" обязательно для заполнения',
            'is_enabled_work_schedule.in' => 'Поле "Включен ли рабочий график" содержит недопустимое значение',

            'api_key_wb.string' => 'Неверный формат ключа',
            'api_key_wb.required' => 'Поле "Ключ API WB" обязательно для заполнения',

            'api_key_ozon.string' => 'Неверный формат ключа',
            'api_key_ozon.required' => 'Поле "Ключ API Ozon" обязательно для заполнения',

            'seller_id_ozon.string' => 'Неверный формат Seller Id',
            'seller_id_ozon.required' => 'Поле "ID продавца Ozon" обязательно для заполнения',

        ];
    }
}
