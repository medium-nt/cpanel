<?php

namespace App\Http\Requests;

use App\Models\Shelf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
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
            'comment' => 'nullable|string|min:5|max:255',
            'inventory_shelf' => [
                'required',
                Rule::in(array_merge(['all'], Shelf::pluck('id')->toArray())),
            ]
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
            'comment.string' => 'Комментарий должен быть строкой',
            'comment.min' => 'Комментарий должен быть не менее :min символов',
            'comment.max' => 'Комментарий должен быть не более :max символов',
            'inventory_shelf.required' => 'Тип инвентаризации обязателен',
            'inventory_shelf.in' => 'Неверно выбран тип инвентаризации',
        ];
    }
}
