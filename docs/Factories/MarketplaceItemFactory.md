# MarketplaceItemFactory

## Модель

`App\Models\MarketplaceItem` - Товар маркетплейса

## Генерируемые поля и их типы данных

| Поле     | Тип данных | Описание        | Метод генерации                                     |
|----------|------------|-----------------|-----------------------------------------------------|
| `title`  | string     | Название товара | `$this->faker->words(3, true)`                      |
| `width`  | integer    | Ширина товара   | `$this->faker->randomElement([150, 200, 250, 300])` |
| `height` | integer    | Высота товара   | `$this->faker->randomElement([250, 270])`           |

## Особые значения и константы

### Предопределенные значения для ширины

- 150
- 200
- 250
- 300

### Предопределенные значения для высоты

- 250
- 270

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание товара маркетплейса
$item = MarketplaceItem::factory()->create();

// Получение доступа к полям
echo $item->title;    // Сгенерированное название из 3 слов
echo $item->width;    // Одно из значений: 150, 200, 250, 300
echo $item->height;   // 250 или 270
```

### Создание с определенными параметрами

```php
// Создание товара с конкретными размерами
$item = MarketplaceItem::factory()->create([
    'title' => 'Специальный товар',
    'width' => 500,
    'height' => 400
]);

// Создание с определенной шириной
$item = MarketplaceItem::factory()->create([
    'width' => 200
]);
```

### Массовое создание

```php
// Создание 10 товаров
$items = MarketplaceItem::factory()->count(10)->create();

// Создание товаров с разной шириной
$items = MarketplaceItem::factory()->count(5)->create([
    'width' => $this->faker->randomElement([150, 200])
]);
```

### В тестах

```php
// Тестирование API
$item = MarketplaceItem::factory()->create();

$response = $this->getJson("/api/marketplace-items/{$item->id}")
    ->assertStatus(200)
    ->assertJson([
        'title' => $item->title,
        'width' => $item->width,
        'height' => $item->height
    ]);
```

### Комбинирование с другими фабриками

```php
// Создание товара и заказа на него
$item = MarketplaceItem::factory()->create();
$order = MarketplaceOrderFactory::new()->create([
    'marketplace_item_id' => $item->id
]);
```

### Создание набора товаров

```php
// Создание товаров разных размеров
$items = [
    MarketplaceItem::factory()->create(['width' => 150, 'height' => 250]),
    MarketplaceItem::factory()->create(['width' => 200, 'height' => 270]),
    MarketplaceItem::factory()->create(['width' => 250, 'height' => 250]),
    MarketplaceItem::factory()->create(['width' => 300, 'height' => 270]),
];

// Или в цикле
foreach ([150, 200, 250, 300] as $width) {
    MarketplaceItem::factory()->create(['width' => $width]);
}
```
