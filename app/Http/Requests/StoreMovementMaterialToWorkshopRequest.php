<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovementMaterialToWorkshopRequest extends FormRequest
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
            'comment' => 'string|min:2|max:255|nullable',
            'material_id.*.required' => 'Материал обязателен.',      // Всегда требуется
            'material_id.*.exists' => 'Материал не найден.',         // Проверяем существование
            'ordered_quantity.*.required' => 'Количество обязательно.',      // Всегда требуется
            'ordered_quantity.*.min' => 'Количество должно быть больше или равно нулю.',  // Разрешаем ноль
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
            'quantity.*.min' => 'Количество должно быть больше нуля.'
        ];
    }
}
