<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MotivationUpdateUsersRequest extends FormRequest
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
            'from' => 'array',
            'from.*' => 'nullable|numeric|min:0|max:255',
            'to' => 'array',
            'to.*' => 'nullable|numeric|min:1|max:255',
            'rate' => 'array',
            'rate.*' => 'nullable|numeric|min:1|max:255',
            'bonus' => 'array',
            'bonus.*' => 'nullable|numeric|min:0|max:255',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $to = $this->input('to', []);

            for ($i = 1; $i < count($to); $i++) {
                if ($to[$i] !== null && $to[$i - 1] !== null && $to[$i] <= $to[$i - 1]) {
                    $validator->errors()
                        ->add("to.$i", "значение \"До\" в строке " . ($i + 1) . " должно быть больше значения в строке " . $i);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'from.array' => 'Ошибка в данных поля "От"',
            'from.*.numeric' => 'Должно быть указано число',
            'from.*.max' => 'Максимально возможное число для поля "От" - 255',
            'from.*.min' => 'Меньше 0 указывать нельзя',

            'to.array' => 'Ошибка в данных поля "До"',
            'to.*.numeric' => 'Должно быть указано число',
            'to.*.max' => 'Максимально возможное число для поля "До" - 255',
            'to.*.min' => 'Меньше 1 в поле "До" указывать нельзя',

            'rate.array' => 'Ошибка в данных поля "зарплата"',
            'rate.*.numeric' => 'Должно быть указано число',
            'rate.*.max' => 'Максимально возможное число для поля "зарплата" - 255',
            'rate.*.min' => 'Меньше 1 в поле "зарплата" указывать нельзя',

            'bonus.array' => 'Ошибка в данных поля "бонус"',
            'bonus.*.numeric' => 'Должно быть указано число',
            'bonus.*.max' => 'Максимально возможное число для поля "бонус" - 255',
            'bonus.*.min' => 'Меньше 0 указывать нельзя',
        ];
    }
}
