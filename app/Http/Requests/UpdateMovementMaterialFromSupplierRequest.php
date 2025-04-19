<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovementMaterialFromSupplierRequest extends FormRequest
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
            'id.*' => 'required|exists:movement_materials,id',
            'price.*' => 'required|numeric|min:0.01',
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
            'id.*.required' => 'Материал обязателен.',
            'id.*.exists' => 'Такой материал не найден.',
            'price.*.required' => 'Цена должна быть обязательно.',
            'price.*.numeric' => 'Цена должна быть числом.',
            'price.*.min' => 'Цена должна быть больше нуля.',
        ];
    }
}
