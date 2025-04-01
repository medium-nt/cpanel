<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsersRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|max:255|email',
            'password' => 'required|confirmed|min:6|string',
            'role_id' => 'required|in:1,2',
            'salary_rate' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле "ФИО" обязательно для заполнения',
            'name.min' => 'Поле "ФИО" должно быть не менее 2 символов',
            'name.max' => 'Поле "ФИО" должно быть не больше 255 символов',
            'email.required' => 'Поле "Email" обязательно для заполнения',
            'email.email' => 'Поле "Email" должно быть адресом электронной почты',
            'password.required' => 'Поле "Пароль" обязательно для заполнения',
            'password.confirmed' => 'Поля "Пароль" и "Подтверждение пароля" должны совпадать',
            'role_id.required' => 'Поле "Роль" обязательно для заполнения',
            'role_id.in' => 'Указана неизвестная роль для пользователя',
            'salary_rate.required' => 'Поле "Ставка" обязательно для заполнения',
            'salary_rate.numeric' => 'Поле "Ставка" должно быть числом',
            'salary_rate.min' => 'Поле "Ставка" должно быть не менее 0',
        ];
    }
}
