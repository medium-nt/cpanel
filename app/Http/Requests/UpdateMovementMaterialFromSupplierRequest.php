<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovementMaterialFromSupplierRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'id.*' => 'required|exists:movement_materials,id',
            'price.*' => 'required|numeric|min:0.01',
            'supplier_id' => 'required|exists:suppliers,id',
        ];
    }

    /**
     * Customize the error messages used by the validator.
     */
    public function messages(): array
    {
        return [
            'id.*.required' => 'Материал обязателен.',
            'id.*.exists' => 'Такой материал не найден.',
            'price.*.required' => 'Цена должна быть обязательно.',
            'price.*.numeric' => 'Цена должна быть числом.',
            'price.*.min' => 'Цена должна быть больше нуля.',
            'supplier_id.required' => 'Поставщик обязателен.',
            'supplier_id.exists' => 'Такой поставщик не найден.',
        ];
    }
}
