<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveGroupWarehouseOfItemRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'material_title' => 'required|string',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'shelf_id' => 'required|integer|exists:shelves,id',
            'quantity' => 'required|numeric|min:1',
        ];
    }

    /**
     * Customize the error messages used by the validator.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'material_title.required' => 'Укажите материал',
            'width.required' => 'Укажите ширину',
            'height.required' => 'Укажите высоту',
            'shelf_id.required' => 'Выберите полку',
            'quantity.required' => 'Укажите количество',
            'quantity.min' => 'Количество должно быть больше нуля',
        ];
    }
}
