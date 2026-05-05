<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovementMaterialToWorkshopRequest extends FormRequest
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
            'comment' => 'string|min:2|max:255|nullable',
            'material_id' => 'required|exists:materials,id',
        ];
    }

    /**
     * Customize the error messages used by the validator.
     */
    public function messages(): array
    {
        return [
            'comment.min' => 'Комментарий должен содержать минимум :min символов.',
            'comment.max' => 'Комментарий должен содержать максимум :max символов.',
            'material_id.required' => 'Материал обязателен.',
            'material_id.exists' => 'Материал не найден.',
        ];
    }
}
