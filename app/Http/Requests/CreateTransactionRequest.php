<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
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
            'amount' => 'required|numeric|gte:0.01',
            'transaction_type' => 'required|in:inflow,outflow',
            'user_id' => 'sometimes|nullable|integer|exists:users,id',
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
            'title.required' => 'Название обязательно',
            'title.string' => 'Название должно быть строкой',
            'title.min' => 'Название должно быть не менее 2 символов',
            'title.max' => 'Название должно быть не более 255 символов',

            'amount.required' => 'Сумма обязательна',
            'amount.numeric' => 'Сумма должна быть числом',
            'amount.gte' => 'Сумма должна быть больше нуля',

            'transaction_type.required' => 'Тип транзакции обязателен',
            'transaction_type.in' => 'Тип транзакции должен быть входящим или исходящим',

            'user_id.integer' => 'Пользователь должен быть числом',
            'user_id.nullable' => 'Пользователь должен быть числом',
            'user_id.exists' => 'Пользователь не найден',
        ];
    }
}
