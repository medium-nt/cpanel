<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMarketplaceItemRequest extends FormRequest
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
            'title' => 'required|string|min:2|max:255',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'ozon_sku' => 'nullable|string|min:3',
            'wb_sku' => 'nullable|string|min:3',
            'material_id.*.required' => 'Материал обязателен.',      // Всегда требуется
            'material_id.*.exists' => 'Материал не найден.',         // Проверяем существование
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
            'title.required' => 'Название обязательно.',
            'title.min' => 'Название должно содержать минимум :min символов.',
            'title.max' => 'Название должно содержать максимум :max символов.',
            'width.required' => 'Ширина обязательна.',
            'height.required' => 'Высота обязательна.',
            'ozon_sku.min' => 'SKU Ozon должен содержать минимум :min символов.',
            'wb_sku.min' => 'SKU Wildberries должен содержать минимум :min символов.',
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'quantity.*.required' => 'Количество обязательно.',
            'quantity.*.min' => 'Количество должно быть больше нуля.',
        ];
    }
}
