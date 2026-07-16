<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RollWriteOffRequest extends FormRequest
{
    /**
     * Авторизация выполняется на уровне роута через policy (writeOff).
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Правила валидации ручного списания метража рулона.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quantity' => ['required', 'numeric', 'gt:0', function (string $attribute, mixed $value, $fail) {
                $roll = $this->route('roll');

                if ($roll && (float) $value > (float) $roll->current_quantity) {
                    $fail("Количество превышает остаток рулона ({$roll->current_quantity}).");
                }
            }],
            'comment' => 'nullable|string|max:1000',
            'back_url' => ['nullable', 'string', function (string $attribute, mixed $value, $fail) {
                if (! $value) {
                    return;
                }
                // Разрешаем только относительные URL или URL того же хоста, что и текущий
                // запрос (защита от open redirect, не зависит от APP_URL).
                $host = parse_url($value, PHP_URL_HOST);
                if ($host !== null && $host !== $this->getHost()) {
                    $fail('Недопустимый адрес возврата.');
                }
            }],
        ];
    }

    /**
     * Сообщения об ошибках валидации на русском.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'quantity.required' => 'Укажите количество для списания.',
            'quantity.numeric' => 'Количество должно быть числом.',
            'quantity.gt' => 'Количество должно быть больше нуля.',
            'comment.max' => 'Комментарий должен содержать максимум :max символов.',
        ];
    }
}
