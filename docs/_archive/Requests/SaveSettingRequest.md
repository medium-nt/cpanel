# SaveSettingRequest

## Назначение класса валидации

Класс `SaveSettingRequest` предназначен для валидации запросов на сохранение
настроек системы, включая рабочее расписание и ключи API для маркетплейсов (
Wildberries и Ozon).

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
        'working_day_start' => 'required|date_format:H:i',
        'working_day_end' => 'required|date_format:H:i',
        'is_enabled_work_schedule' => 'required|in:0,1',
        'api_key_wb' => 'sometimes|nullable|string',
        'api_key_ozon' => 'sometimes|nullable|string',
        'seller_id_ozon' => 'sometimes|nullable|string',
    ];
}
```

### Поля валидации:

- **`working_day_start`** - Время начала рабочего дня
    - `required` - Поле обязательно для заполнения
    - `date_format:H:i` - Должно соответствовать формату времени ЧЧ:ММ (
      24-часовой формат)

- **`working_day_end`** - Время окончания рабочего дня
    - `required` - Поле обязательно для заполнения
    - `date_format:H:i` - Должно соответствовать формату времени ЧЧ:ММ (
      24-часовой формат)

- **`is_enabled_work_schedule`** - Флаг включения рабочего графика
    - `required` - Поле обязательно для заполнения
    - `in:0,1` - Значение должно быть 0 (выключено) или 1 (включено)

- **`api_key_wb`** - API ключ для Wildberries
    - `sometimes` - Поле может отсутствовать в запросе
    - `nullable` - Может быть пустым (null)
    - `string` - Должно быть строкой

- **`api_key_ozon`** - API ключ для Ozon
    - `sometimes` - Поле может отсутствовать в запросе
    - `nullable` - Может быть пустым (null)
    - `string` - Должно быть строкой

- **`seller_id_ozon`** - ID продавца на Ozon
    - `sometimes` - Поле может отсутствовать в запросе
    - `nullable` - Может быть пустым (null)
    - `string` - Должно быть строкой

## Кастомные сообщения об ошибках (метод messages)

```php
public function messages(): array
{
    return [
        'working_day_start.date_format' => 'Неверный формат времени начала рабочего дня',
        'working_day_start.required' => 'Поле "Начало рабочего дня" обязательно для заполнения',

        'working_day_end.date_format' => 'Неверный формат времени конца рабочего дня',
        'working_day_end.required' => 'Поле "Конец рабочего дня" обязательно для заполнения',

        'is_enabled_work_schedule.required' => 'Поле "Включен ли рабочий график" обязательно для заполнения',
        'is_enabled_work_schedule.in' => 'Поле "Включен ли рабочий график" содержит недопустимое значение',

        'api_key_wb.string' => 'Неверный формат ключа',
        'api_key_wb.required' => 'Поле "Ключ API WB" обязательно для заполнения',

        'api_key_ozon.string' => 'Неверный формат ключа',
        'api_key_ozon.required' => 'Поле "Ключ API Ozon" обязательно для заполнения',

        'seller_id_ozon.string' => 'Неверный формат Seller Id',
        'seller_id_ozon.required' => 'Поле "ID продавца Ozon" обязательно для заполнения',
    ];
}
```

## Кастомные атрибуты (метод attributes)

В данном классе метод `attributes` не определен.

## Особенности валидации

1. **Формат времени** - Время должно строго соответствовать формату 24-часового
   формата (ЧЧ:ММ)
2. **Условные поля API** - Поля для API ключей являются опциональными и могут
   быть пустыми
3. **Булев флаг** - Поле `is_enabled_work_schedule` ожидает числовое
   представление (0 или 1)
4. **Валидация рабочего времени** - Хотя в правилах не проверяется логическое
   соотношение между началом и концом рабочего дня, предполагается, что конец
   дня должен быть позже начала

## Используемые в контроллерах

Данный Form Request используется в контроллере настроек для сохранения системных
параметров:

```php
// Пример использования в контроллере
public function save(SaveSettingRequest $request)
{
    $validated = $request->validated();
    // Сохранение настроек в базе данных или конфигурационном файле
}
```

Примеры валидных данных:

```json
{
    "working_day_start": "09:00",
    "working_day_end": "18:00",
    "is_enabled_work_schedule": "1",
    "api_key_wb": "wb-api-key-string",
    "api_key_ozon": null,
    "seller_id_ozon": "12345"
}
```
