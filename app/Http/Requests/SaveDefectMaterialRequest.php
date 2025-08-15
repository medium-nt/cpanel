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
            'comment' => 'nullable|string|min:2|max:255',
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'quantity.*.required' => 'Количество обязательно.',
            'quantity.*.min' => 'Количество должно быть больше или равно нулю.',
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
