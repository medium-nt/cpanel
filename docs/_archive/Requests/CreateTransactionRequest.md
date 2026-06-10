# CreateTransactionRequest

## Назначение класса валидации

Класс `CreateTransactionRequest` предназначен для валидации запросов на создание
финансовых транзакций в системе. Этот класс используется для учета всех денежных
операций - как доходов, так и расходов.

## Правила авторизации (метод authorize)

```php
public function authorize(): bool
{
    return true;
}
```

- Все авторизованные пользователи имеют право выполнять данный запрос
- Дополнительные проверки авторизации не требуются

## Правила валидации (метод rules)

```php
public function rules(): array
{
    return [
        'title' => 'required|string|min:2|max:255',
        'accrual_for_date' => 'required|date|before_or_equal:today',
        'amount' => 'required|numeric|gte:0.01',
        'transaction_type' => 'required|in:in,out',
        'user_id' => 'nullable|integer|exists:users,id',
    ];
}
```

### Поля валидации:

- **`title`** - Название/описание транзакции
    - `required` - Поле обязательно для заполнения
    - `string` - Должно быть строкой
    - `min:2` - Минимальная длина - 2 символа
    - `max:255` - Максимальная длина - 255 символов

- **`accrual_for_date`** - Дата начисления транзакции
    - `required` - Поле обязательно для заполнения
    - `date` - Должно быть валидной датой
    - `before_or_equal:today` - Дата не должна быть позже текущей

- **`amount`** - Сумма транзакции
    - `required` - Поле обязательно для заполнения
    - `numeric` - Должно быть числом
    - `gte:0.01` - Значение должно быть больше или равно 0.01

- **`transaction_type`** - Тип транзакции
    - `required` - Поле обязательно для заполнения
    - `in:in,out` - Значение должно быть либо "in" (доход), либо "out" (расход)

- **`user_id`** - ID пользователя, к которому относится транзакция
    - `nullable` - Может быть пустым (null)
    - `integer` - Должно быть целым числом
    - `exists:users,id` - Пользователь должен существовать в таблице `users`

## Кастомные сообщения об ошибках (метод messages)

```php
public function messages(): array
{
    return [
        'title.required' => 'Название обязательно',
        'title.string' => 'Название должно быть строкой',
        'title.min' => 'Название должно быть не менее 2 символов',
        'title.max' => 'Название должно быть не более 255 символов',

        'accrual_for_date.required' => 'Дата начисления обязательна',
        'accrual_for_date.date' => 'Дата начисления должна быть датой',
        'accrual_for_date.before_or_equal' => 'Дата начисления не должна быть больше текущей даты',

        'amount.required' => 'Сумма обязательна',
        'amount.numeric' => 'Сумма должна быть числом',
        'amount.gte' => 'Сумма должна быть больше нуля',

        'transaction_type.required' => 'Тип транзакции обязателен',
        'transaction_type.in' => 'Тип транзакции должен быть входящим или исходящим',

        'user_id.integer' => 'Id пользователя должно быть числом',
        'user_id.exists' => 'Пользователь не найден',
    ];
}
```

## Кастомные атрибуты (метод attributes)

В данном классе метод `attributes` не определен.

## Особенности валидации

1. **Ограничение по дате** - Запрещено создавать транзакции с будущими датами,
   что обеспечивает корректность финансового учета

2. **Минимальная сумма** - Сумма транзакции не может быть меньше 0.01, что
   предотвращает создание транзакций с нулевой или отрицательной суммой

3. **Типы транзакций** - Поддерживаются только два типа:
    - **`in`** - входящая транзакция (доход)
    - **`out`** - исходящая транзакция (расход)

4. **Связь с пользователем** - Транзакция может быть привязана к конкретному
   пользователю, но это не обязательно

5. **Независимость от валюты** - Сумма валидируется только как число, без
   проверки конкретной валюты

## Используемые в контроллерах

Данный Form Request используется в контроллере транзакций для создания новых
финансовых записей:

```php
// Пример использования в контроллере
public function store(CreateTransactionRequest $request)
{
    $validated = $request->validated();

    $transaction = new Transaction();
    $transaction->title = $validated['title'];
    $transaction->accrual_for_date = $validated['accrual_for_date'];
    $transaction->amount = $validated['amount'];
    $transaction->transaction_type = $validated['transaction_type'];
    $transaction->user_id = $validated['user_id'];
    $transaction->save();

    return redirect()->route('transactions.index');
}
```

Пример валидных данных:

```json
{
    "title": "Покупка канцелярии для офиса",
    "accrual_for_date": "2025-12-01",
    "amount": "1500.50",
    "transaction_type": "out",
    "user_id": "123"
}
```

Пример транзакции без привязки к пользователю:

```json
{
    "title": "Оплата аренды помещения",
    "accrual_for_date": "2025-12-01",
    "amount": "50000",
    "transaction_type": "out",
    "user_id": null
}
```
