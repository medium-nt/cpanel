<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * Доступ открыт любому аутентифицированному сотруднику (роут под middleware auth).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации создания тикета.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:5000'],
            'page_url' => ['nullable', 'url', 'max:500'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
