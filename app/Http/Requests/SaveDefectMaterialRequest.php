<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDefectMaterialRequest extends FormRequest
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
            'comment' => 'required|string|min:2|max:255',
            'material_id.*.required' => 'Материал обязателен.',      // Всегда требуется
            'material_id.*.exists' => 'Материал не найден.',         // Проверяем существование
            'quantity.*.required' => 'Количество обязательно.',      // Всегда требуется
            'quantity.*.min' => 'Количество должно быть больше или равно нулю.',  // Разрешаем ноль
            'type_movement_id' => 'required|in:4,7'
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
            'comment.required' => 'Комментарий обязательно.',
            'comment.min' => 'Комментарий должен содержать минимум :min символов.',
            'comment.max' => 'Комментарий должен содержать максимум :max символов.',
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'quantity.*.required' => 'Количество обязательно.',
            'quantity.*.min' => 'Количество должно быть больше нуля.',
            'type_movement_id.required' => 'Тип перемещения не верен. Выберете "брак" или "остаток"',
            'type_movement_id.in' => 'Тип перемещения не верен. Выберете "брак" или "остаток"'
        ];
    }
}
