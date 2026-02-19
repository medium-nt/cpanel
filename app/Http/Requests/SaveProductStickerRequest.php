<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveProductStickerRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'print_type' => 'nullable|string|max:255',
            'material' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'fastening_type' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Название обязательно.',
            'title.max' => 'Название должно содержать максимум :max символов.',
            'color.max' => 'Цвет должен содержать максимум :max символов.',
            'print_type.max' => 'Тип печати должен содержать максимум :max символов.',
            'material.max' => 'Материал должен содержать максимум :max символов.',
            'country.max' => 'Страна должна содержать максимум :max символов.',
            'fastening_type.max' => 'Тип закрепления должен содержать максимум :max символов.',
        ];
    }
}
