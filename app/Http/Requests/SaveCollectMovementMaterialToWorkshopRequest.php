<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCollectMovementMaterialToWorkshopRequest extends FormRequest
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
            'material_id.*.required' => 'Материал обязателен.',      // Всегда требуется
            'material_id.*.exists:movement_materials,id' => 'Материал не найден.',         // Проверяем существование
            'quantity.*.required' => 'Количество обязательно.',      // Всегда требуется
            'quantity.*.min' => 'Количество должно быть больше или равно нулю.',  // Разрешаем ноль
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
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'quantity.*.required' => 'Количество обязательно.',
            'quantity.*.min' => 'Количество должно быть больше нуля.',
        ];
    }
}
