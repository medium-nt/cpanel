<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RateUpdateUsersRequest extends FormRequest
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
            'width' => 'array',
            'width.*' => 'required|numeric|min:200|max:800',
            'rate' => 'array',
            'rate.*' => 'nullable|numeric|min:1|max:1000',
            'not_cutter_rate' => 'array',
            'not_cutter_rate.*' => 'nullable|numeric|min:1|max:1000',
            'cutter_rate' => 'array',
            'cutter_rate.*' => 'nullable|numeric|min:1|max:1000',

        ];
    }

    public function messages(): array
    {
        return [
            'width.*.required' => 'Поле обязательно для заполнения',
            'width.*.numeric' => 'Поле должно быть числом',
            'width.*.min' => 'Ширина должна быть не меньше 200',
            'width.*.max' => 'Ширина должна быть не больше 800',

            'rate.*.numeric' => 'Поле должно быть числом',
            'rate.*.min' => 'Оплата с закроем не должна быть меньше 1',
            'rate.*.max' => 'Оплата с закроем не должна быть больше 1000',

            'not_cutter_rate.*.numeric' => 'Поле должно быть числом',
            'not_cutter_rate.*.min' => 'Оплата без кроя не должна быть меньше 1',
            'not_cutter_rate.*.max' => 'Оплата без кроя не должна быть больше 1000',

            'cutter_rate.*.numeric' => 'Поле должно быть числом',
            'cutter_rate.*.min' => 'Оплата закройщику не должна быть меньше 1',
            'cutter_rate.*.max' => 'Оплата закройщику не должна быть больше 1000',
        ];
    }
}
