# SaveMarketplaceItemRequest

## Назначение класса валидации

Класс `SaveMarketplaceItemRequest` предназначен для валидации запросов на
создание и сохранение товаров для маркетплейсов. Этот класс используется для
добавления новых товаров в систему с указанием их характеристик и привязкой к
материалам.

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
        'width' => 'required|integer',
        'height' => 'required|integer',
        'ozon_sku' => 'nullable|string|min:3',
        'wb_sku' => 'nullable|string|min:3',
        'material_id.*.required' => 'Материал обязателен.',
        'material_id.*.exists' => 'Материал не найден.',
        'quantity.*.required' => 'Количество обязательно.',
        'quantity.*.min' => 'Количество должно быть больше или равно нулю.',
    ];
}
```

### Поля валидации:

- **`title`** - Название товара
    - `required` - Поле обязательно для заполнения
    - `string` - Должно быть строкой
    - `min:2` - Минимальная длина - 2 символа
    - `max:255` - Максимальная длина - 255 символов

- **`width`** - Ширина товара
    - `required` - Поле обязательно для заполнения
    - `integer` - Должно быть целым числом

- **`height`** - Высота товара
    - `required` - Поле обязательно для заполнения
    - `integer` - Должно быть целым числом

- **`ozon_sku`** - SKU для Ozon
    - `nullable` - Может быть пустым (null)
    - `string` - Должно быть строкой
    - `min:3` - Минимальная длина - 3 символа

- **`wb_sku`** - SKU для Wildberries
    - `nullable` - Может быть пустым (null)
    - `string` - Должно быть строкой
    - `min:3` - Минимальная длина - 3 символа

- **`material_id.*`** - Массив идентификаторов материалов
    - Правило определено некорректно - содержит сообщение об ошибке вместо
      правил валидации
    - Корректные правила должны быть: `required|exists:materials,id`

- **`quantity.*`** - Массив количеств материалов
    - Правило определено некорректно - содержит сообщение об ошибке вместо
      правил валидации
    - Корректные правила должны быть: `required|integer|min:0`

## Кастомные сообщения об ошибках (метод messages)

```php
public function messages(): array
{
    return [
        'title.required' => 'Название обязательно.',
        'title.min' => 'Название должно содержать минимум :min символов.',
        'title.max' => 'Название должно содержать максимум :max символов.',
        'width.required' => 'Ширина обязательна.',
        'height.required' => 'Высота обязательна.',
        'ozon_sku.min' => 'SKU Ozon должен содержать минимум :min символов.',
        'wb_sku.min' => 'SKU Wildberries должен содержать минимум :min символов.',
        'material_id.*.required' => 'Материал обязателен.',
        'material_id.*.exists' => 'Материал не найден.',
        'quantity.*.required' => 'Количество обязательно.',
        'quantity.*.min' => 'Количество должно быть больше нуля.',
    ];
}
```

## Кастомные атрибуты (метод attributes)

В данном классе метод `attributes` не определен.

## Особенности валидации

1. **Поддержка маркетплейсов** - Отдельные поля для SKU популярных
   маркетплейсов:
    - `ozon_sku` - для Ozon
    - `wb_sku` - для Wildberries

2. **Некорректная структура правил** - В методе `rules()` правила для массивов
   определены в неверном формате:
    - Вместо правил валидации указаны сообщения об ошибках
    - Правильный формат для `material_id.*`:
      `'material_id.*' => 'required|exists:materials,id'`

3. **Массивная валидация материалов** - Класс ожидает получение массивов
   материалов и их количеств для создания товара

4. **Обязательные размеры** - Ширина и высота являются обязательными полями и
   должны быть целыми числами

5. **Минимальная длина SKU** - Для маркетплейсов требуется минимальная длина
   SKU - 3 символа

6. **Опциональные поля маркетплейсов** - Поля SKU не являются обязательными, что
   позволяет создавать товары без привязки к конкретным маркетплейсам

7. **Несоответствие сообщений** - В методе `messages()` указано сообщение "
   Количество должно быть больше нуля", что противоречит комментарию в правилах

## Используемые в контроллерах

Данный Form Request используется в контроллере управления товарами
маркетплейсов:

```php
// Пример использования в контроллере
public function save(SaveMarketplaceItemRequest $request)
{
    $validated = $request->validated();

    // Логика создания товара для маркетплейса
    $marketplaceItem = new MarketplaceItem();
    $marketplaceItem->title = $validated['title'];
    $marketplaceItem->width = $validated['width'];
    $marketplaceItem->height = $validated['height'];
    $marketplaceItem->ozon_sku = $validated['ozon_sku'];
    $marketplaceItem->wb_sku = $validated['wb_sku'];
    $marketplaceItem->save();

    // Связь с материалами
    foreach ($validated['material_id'] as $index => $materialId) {
        $marketplaceItem->materials()->attach($materialId, [
            'quantity' => $validated['quantity'][$index]
        ]);
    }

    return redirect()->route('marketplace.items.index');
}
```

Пример валидных данных:

```json
{
    "title": "Платье вечернее шелковое",
    "width": "50",
    "height": "150",
    "ozon_sku": "OZN12345",
    "wb_sku": "WB67890",
    "material_id": ["123", "456"],
    "quantity": ["2", "1"]
}
```

## Заметки о реализации

В коде класса присутствуют ошибки в определении правил валидации, которые
рекомендуется исправить:

```php
// Правильная структура правил
public function rules(): array
{
    return [
        'title' => 'required|string|min:2|max:255',
        'width' => 'required|integer',
        'height' => 'required|integer',
        'ozon_sku' => 'nullable|string|min:3',
        'wb_sku' => 'nullable|string|min:3',
        'material_id.*' => 'required|exists:materials,id',
        'quantity.*' => 'required|integer|min:0',
    ];
}
```

Этот класс является ключевым для управления каталогом товаров, предназначенных
для продажи на маркетплейсах.
