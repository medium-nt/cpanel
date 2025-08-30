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
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:255',
            'accrual_for_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|gte:0.01',
            'transaction_type' => 'required|in:in,out',
            'user_id' => 'nullable|integer|exists:users,id',
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
            'title.required' => 'Название обязательно',
            'title.string' => 'Название должно быть строкой',
            'title.min' => 'Название должно быть не менее 2 символов',
            'title.max' => 'Название должно быть не более 255 символов',

            'accrual_for_date.required' => 'Дата начисления обязательна',
            'accrual_for_date.date' => 'Дата начисления должна быть датой',
            'accrual_for_date.before_or_equal' => 'Дата начисления не должна быть больше текущей даты',

            'amount.required' => 'Сумма обязательна',
            'amount.numeric' => 'Сумма должна быть числом',
            'amount.gte' => 'Сумма должна быть больше нуля',

            'transaction_type.required' => 'Тип транзакции обязателен',
            'transaction_type.in' => 'Тип транзакции должен быть входящим или исходящим',

            'user_id.integer' => 'Id пользователя должно быть числом',
            'user_id.exists' => 'Пользователь не найден',
        ];
    }
}
