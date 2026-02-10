<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketplaceOrderRequest extends FormRequest
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
            'order_id' => 'required|unique:marketplace_orders,order_id',
            'marketplace_id' => 'required',
            'item_id.*' => 'required|exists:marketplace_items,id',
            'quantity.*' => 'integer|min:1',
            'fulfillment_type' => 'required|in:FBO,FBS',
            'cluster' => 'required_if:fulfillment_type,FBO|string',
        ];
    }

    /**
     * Customize the error messages used by the validator.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Номер заказа обязателен.',
            'order_id.unique' => 'Заказ с таким номером уже существует.',
            'marketplace_id.required' => 'Маркетплейс обязателен.',
            'item_id.*.required' => 'Пожалуйста, выберите товар.',
            'item_id.*.exists' => 'Такой товар не найден.',
            'quantity.required' => 'Пожалуйста, введите количество товара.',
            'quantity.*.integer' => 'Количество товара должно быть целым числом.',
            'quantity.*.min' => 'Количество товара должно быть больше 0.',
            'fulfillment_type.required' => 'Тип фулфилмента обязателен.',
            'fulfillment_type.in' => 'Тип фулфилмента должен быть "FBO" или "FBS".',
            'cluster.string' => 'Не верно указан кластер',
            'cluster.required_if' => 'Не указан кластер',
        ];
    }
}
