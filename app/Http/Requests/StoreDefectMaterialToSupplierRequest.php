<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDefectMaterialToSupplierRequest extends FormRequest
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
            'supplier_id.required|exists:suppliers,id' => 'Поставщик обязателен.',
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists:movement_materials,id' => 'Материал не найден.',         // Проверяем существование
            'quantity.*.required' => 'Количество обязательно.',      // Всегда требуется
            'quantity.*.min' => 'Количество должно быть больше или равно нулю.',  // Разрешаем ноль
            'comment' => 'string|min:3|max:255|nullable'
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
            'supplier_id.required' => 'Поставщик обязателен.',
            'supplier_id.exists' => 'Поставщик не найден.',
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'quantity.*.required' => 'Количество обязательно.',
            'quantity.*.min' => 'Количество должно быть больше нуля.',
            'comment' => 'Комментарий должен быть не менее 3 символов.'
        ];
    }
}
