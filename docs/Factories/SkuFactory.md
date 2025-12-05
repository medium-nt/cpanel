# SkuFactory

## Модель

`App\Models\Sku` - SKU (Stock Keeping Unit)

## Генерируемые поля и их типы данных

| Поле             | Тип данных | Описание               | Метод генерации                                 |
|------------------|------------|------------------------|-------------------------------------------------|
| `item_id`        | integer    | ID товара маркетплейса | `MarketplaceItem::factory()`                    |
| `sku`            | string     | Уникальный SKU код     | `$this->faker->unique()->numerify('SKU-#####')` |
| `marketplace_id` | integer    | ID маркетплейса        | `$this->faker->numberBetween(1, 2)`             |

## Особые значения и константы

### Формат SKU

Генерируется в формате: `SKU-XXXXX`, где X - цифры (5 знаков)
Примеры: `SKU-12345`, `SKU-67890`

### ID маркетплейсов

- `1` - Первый маркетплейс
- `2` - Второй маркетплейс

### Уникальность

- `sku` - уникальное значение для каждой записи

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `MarketplaceItem` - создается новая запись товара через фабрику

## Примеры использования

### Базовое использование

```php
// Создание SKU с автоматически созданным товаром
$sku = Sku::factory()->create();

// Получение доступа к полям
echo $sku->item_id;        // ID созданного товара
echo $sku->sku;            // Например: "SKU-12345"
echo $sku->marketplace_id; // 1 или 2
```

### Создание с существующим товаром

```php
// Использование существующего товара
$item = MarketplaceItem::factory()->create();

$sku = Sku::factory()->create([
    'item_id' => $item->id,
    'sku' => 'CUSTOM-001',
    'marketplace_id' => 1
]);
```

### Создание с определенным SKU

```php
// Создание с кастомным SKU
$sku = Sku::factory()->create([
    'sku' => 'OZON-PRODUCT-001',
    'marketplace_id' => 1
]);
```

### Создание для разных маркетплейсов

```php
// Создание SKU для OZON
$ozonSku = Sku::factory()->create([
    'marketplace_id' => 1,
    'sku' => 'OZON-001'
]);

// Создание SKU для WB
$wbSku = Sku::factory()->create([
    'marketplace_id' => 2,
    'sku' => 'WB-001'
]);
```

### Массовое создание

```php
// Создание 10 SKU
$skus = Sku::factory()->count(10)->create();

// Создание 5 SKU для одного товара
$item = MarketplaceItem::factory()->create();
$skus = Sku::factory()->count(5)->create(['item_id' => $item->id]);

// Создание SKU для разных маркетплейсов
$ozonSkus = Sku::factory()->count(5)->create(['marketplace_id' => 1]);
$wbSkus = Sku::factory()->count(5)->create(['marketplace_id' => 2]);
```

### Создание с использованием sequence

```php
// Создание SKU с последовательными номерами
Sku::factory()->count(5)->sequence(
    ['sku' => 'SKU-00001'],
    ['sku' => 'SKU-00002'],
    ['sku' => 'SKU-00003'],
    ['sku' => 'SKU-00004'],
    ['sku' => 'SKU-00005']
)->create();
```

### В тестах

```php
// Тестирование API
$sku = Sku::factory()->create();

$response = $this->getJson("/api/skus/{$sku->id}")
    ->assertStatus(200)
    ->assertJson([
        'item_id' => $sku->item_id,
        'sku' => $sku->sku,
        'marketplace_id' => $sku->marketplace_id
    ]);

// Тестирование поиска по SKU
$response = $this->getJson("/api/skus?search={$sku->sku}")
    ->assertStatus(200)
    ->assertJsonFragment(['sku' => $sku->sku]);
```

### Комбинирование с другими фабриками

```php
// Создание товара с несколькими SKU для разных маркетплейсов
$item = MarketplaceItem::factory()->create();

$ozonSku = Sku::factory()->create([
    'item_id' => $item->id,
    'marketplace_id' => 1
]);

$wbSku = Sku::factory()->create([
    'item_id' => $item->id,
    'marketplace_id' => 2
]);

// Создание заказа маркетплейса и привязка SKU
$order = MarketplaceOrder::factory()->create(['marketplace_id' => 1]);
$orderItem = MarketplaceOrderItemFactory::new()->create([
    'marketplace_order_id' => $order->id,
    'marketplace_item_id' => $item->id
]);
```

### Создание для разных категорий товаров

```php
// Создание товаров разных категорий и их SKU
$categories = ['clothing', 'electronics', 'home', 'books'];

foreach ($categories as $category) {
    $item = MarketplaceItem::factory()->create([
        'title' => "Product from {$category}"
    ]);

    Sku::factory()->create([
        'item_id' => $item->id,
        'sku' => strtoupper($category) . '-' . str_pad($item->id, 5, '0', STR_PAD_LEFT)
    ]);
}
```

### Создание с предопределенными SKU

```php
// Создание SKU с осмысленными кодами
$meaningfulSkus = [
    ['sku' => 'OZON-SHIRT-L-BLUE', 'marketplace_id' => 1],
    ['sku' => 'OZON-PANTS-M-BLACK', 'marketplace_id' => 1],
    ['sku' => 'WB-SHIRT-L-BLUE', 'marketplace_id' => 2],
    ['sku' => 'WB-PANTS-M-BLACK', 'marketplace_id' => 2],
];

foreach ($meaningfulSkus as $skuData) {
    Sku::factory()->create($skuData);
}
```

### Создание с иерархией SKU

```php
// Создание SKU с иерархической структурой
$item = MarketplaceItem::factory()->create();

// Основной SKU
$mainSku = Sku::factory()->create([
    'item_id' => $item->id,
    'sku' => 'BASE-001'
]);

// Вариации SKU
$variations = ['RED', 'BLUE', 'GREEN'];
foreach ($variations as $color) {
    Sku::factory()->create([
        'item_id' => $item->id,
        'sku' => 'BASE-001-' . $color
    ]);
}
```

### Создание для отслеживания остатков

```php
// Создание SKU с информацией о остатках (если модель поддерживает)
$skus = Sku::factory()->count(5)->create();

foreach ($skus as $sku) {
    // Предполагая связь с моделью Inventory
    Inventory::factory()->create([
        'sku_id' => $sku->id,
        'quantity' => rand(10, 100),
        'reserved' => rand(0, 10)
    ]);
}
```

### Создание с разными форматами SKU

```php
// Создание SKU в разных форматах
$skuFormats = [
    ['prefix' => 'OZ', 'separator' => '-'],
    ['prefix' => 'WB', 'separator' => '_'],
    ['prefix' => 'MP', 'separator' => '|']
];

foreach ($skuFormats as $format) {
    Sku::factory()->create([
        'sku' => $format['prefix'] . $format['separator'] . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)
    ]);
}
```

### Создание для аналитики

```php
// Создание данных для анализа SKU по маркетплейсам
$marketplaces = [1, 2];
$items = MarketplaceItem::factory()->count(10)->create();

foreach ($items as $item) {
    // Каждый товар есть на всех маркетплейсах
    foreach ($marketplaces as $marketplaceId) {
        Sku::factory()->create([
            'item_id' => $item->id,
            'marketplace_id' => $marketplaceId,
            'sku' => 'MP' . $marketplaceId . '-ITEM' . str_pad($item->id, 4, '0', STR_PAD_LEFT)
        ]);
    }
}
```
