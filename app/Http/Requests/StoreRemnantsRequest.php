<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemnantsRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'material_id' => ['required', 'array'],
            'material_id.*' => ['required', 'exists:materials,id'],
            'ordered_quantity' => ['required', 'array'],
            'ordered_quantity.*' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'comment' => ['nullable', 'string', 'min:3', 'max:255'],
        ];
    }

    /**
     * Customize the error messages used by the validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'material_id.required' => 'Заполните правильно список материалов и количество.',
            'material_id.array' => 'Заполните правильно список материалов и количество.',
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'ordered_quantity.required' => 'Количество обязательно.',
            'ordered_quantity.array' => 'Количество обязательно.',
            'ordered_quantity.*.required' => 'Количество обязательно.',
            'ordered_quantity.*.numeric' => 'Количество должно быть числом.',
            'ordered_quantity.*.min' => 'Количество должно быть больше нуля.',
            'ordered_quantity.*.max' => 'Слишком большое количество.',
            'comment.min' => 'Комментарий должен быть не менее 3 символов.',
        ];
    }
}
