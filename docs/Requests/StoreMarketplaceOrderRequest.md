# StoreMarketplaceOrderRequest

## Назначение класса валидации

Класс `StoreMarketplaceOrderRequest` предназначен для валидации запросов на
создание новых заказов с маркетплейсов. Он проверяет корректность данных о
заказе, включая номер заказа, маркетплейс, товары и тип фулфилмента.

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
        'order_id' => 'required|unique:marketplace_orders,order_id',
        'marketplace_id' => 'required',
        'item_id' => 'required|exists:marketplace_items,id',
        'quantity.*' => 'integer|min:1',
        'fulfillment_type' => 'required|in:FBO,FBS',
    ];
}
```

### Поля валидации:

- **`order_id`** - Уникальный идентификатор заказа
    - `required` - Поле обязательно для заполнения
    - `unique:marketplace_orders,order_id` - Значение должно быть уникальным в
      таблице `marketplace_orders` в колонке `order_id`

- **`marketplace_id`** - Идентификатор маркетплейса
    - `required` - Поле обязательно для заполнения

- **`item_id`** - Идентификатор товара
    - `required` - Поле обязательно для заполнения
    - `exists:marketplace_items,id` - Товар должен существовать в таблице
      `marketplace_items`

- **`quantity.*`** - Количество товаров (массив значений)
    - `integer` - Значение должно быть целым числом
    - `min:1` - Минимальное значение - 1

- **`fulfillment_type`** - Тип выполнения заказа
    - `required` - Поле обязательно для заполнения
    - `in:FBO,FBS` - Значение должно быть либо "FBO" (Fulfillment by Operator),
      либо "FBS" (Fulfillment by Seller)

## Кастомные сообщения об ошибках (метод messages)

```php
public function messages(): array
{
    return [
        'order_id.required' => 'Номер заказа обязателен.',
        'order_id.unique' => 'Заказ с таким номером уже существует.',
        'marketplace_id.required' => 'Маркетплейс обязателен.',
        'item_id.required' => 'Пожалуйста, выберите товар.',
        'item_id.exists' => 'Такой товар не найден.',
        'quantity.required' => 'Пожалуйста, введите количество товара.',
        'quantity.*.integer' => 'Количество товара должно быть целым числом.',
        'quantity.*.min' => 'Количество товара должно быть больше 0.',
        'fulfillment_type.required' => 'Тип фулфилмента обязателен.',
        'fulfillment_type.in' => 'Тип фулфилмента должен быть "FBO" или "FBS".',
    ];
}
```

## Кастомные атрибуты (метод attributes)

В данном классе метод `attributes` не определен.

## Особенности валидации

1. **Уникальность заказа** - Проверяется уникальность `order_id` в таблице
   `marketplace_orders`
2. **Массивное количество** - Поле `quantity` ожидается как массив, что
   позволяет указывать количество для разных позиций в заказе
3. **Типы фулфилмента** - Поддерживаются только два типа:
    - **FBO** (Fulfillment by Operator) - выполнение силами
      оператора/маркетплейса
    - **FBS** (Fulfillment by Seller) - выполнение силами продавца
4. **Существование товара** - Проверяется, что указанный товар существует в базе
   данных

## Используемые в контроллерах

Данный Form Request используется в контроллере заказов маркетплейсов для
создания новых записей:

```php
// Пример использования в контроллере
public function store(StoreMarketplaceOrderRequest $request)
{
    $validated = $request->validated();
    // Создание нового заказа с маркетплейса
}
```

Пример валидных данных:

```json
{
    "order_id": "12345678",
    "marketplace_id": "1",
    "item_id": "123",
    "quantity": ["2", "5"],
    "fulfillment_type": "FBO"
}
```

Примечание: Поле `marketplace_id` имеет только правило `required`, что означает,
что любая строка будет принята, но, вероятно, в контроллере есть дополнительная
проверка на существование маркетплейса.
