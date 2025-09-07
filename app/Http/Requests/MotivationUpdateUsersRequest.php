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
            'bonus' => 'array',
            'bonus.*' => 'nullable|numeric|min:0|max:255',
            'not_cutter_bonus' => 'array',
            'not_cutter_bonus.*' => 'nullable|numeric|min:0|max:255',
            'cutter_bonus' => 'array',
            'cutter_bonus.*' => 'nullable|numeric|min:0|max:255',
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

            'bonus.array' => 'Ошибка в данных поля "бонус с закроем"',
            'bonus.*.numeric' => 'Должно быть указано число',
            'bonus.*.max' => 'Максимально возможное число для поля "бонус с закроем" - 255',
            'bonus.*.min' => 'Меньше 0 указывать нельзя',

            'not_cutter_bonus.array' => 'Ошибка в данных поля "бонус без кроя"',
            'not_cutter_bonus.*.numeric' => 'Должно быть указано число',
            'not_cutter_bonus.*.max' => 'Максимально возможное число для поля "бонус без кроя" - 255',
            'not_cutter_bonus.*.min' => 'Меньше 0 указывать нельзя',

            'cutter_bonus.array' => 'Ошибка в данных поля "бонус закройщика"',
            'cutter_bonus.*.numeric' => 'Должно быть указано число',
            'cutter_bonus.*.max' => 'Максимально возможное число для поля "бонус закройщика" - 255',
            'cutter_bonus.*.min' => 'Меньше 0 указывать нельзя',
        ];
    }
}
