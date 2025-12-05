# MarketplaceOrderItemFactory

## Модель

`App\Models\MarketplaceOrderItem` - Позиция заказа маркетплейса

## Генерируемые поля и их типы данных

| Поле                   | Тип данных | Описание               | Метод генерации                          |
|------------------------|------------|------------------------|------------------------------------------|
| `marketplace_order_id` | integer    | ID заказа маркетплейса | `MarketplaceOrder::factory()`            |
| `marketplace_item_id`  | integer    | ID товара маркетплейса | `MarketplaceItem::factory()`             |
| `quantity`             | integer    | Количество товара      | `$this->faker->numberBetween(1, 5)`      |
| `price`                | integer    | Цена товара            | `$this->faker->numberBetween(100, 1000)` |
| `status`               | integer    | Статус позиции         | `0` (new)                                |
| `seamstress_id`        | integer    | ID швеи                | `0`                                      |
| `cutter_id`            | integer    | null                   | ID закройщика                            | `null` |
| `completed_at`         | datetime   | null                   | Время завершения                         | `null` |
| `cutting_completed_at` | datetime   | null                   | Время завершения раскроя                 | `null` |

## Особые значения и константы

### Статусы

- `0` - новый заказ (new)

### ID сотрудников

- `seamstress_id` = `0` - значение по умолчанию (нет назначенной швеи)
- `cutter_id` = `null` - значение по умолчанию (нет назначенного закройщика)

### Временные метки

- `completed_at` = `null` - не завершено
- `cutting_completed_at` = `null` - раскрой не завершен

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `MarketplaceOrder` - создается новая запись заказа через фабрику
- `MarketplaceItem` - создается новая запись товара через фабрику

## Примеры использования

### Базовое использование

```php
// Создание позиции заказа с автоматически созданным заказом и товаром
$item = MarketplaceOrderItem::factory()->create();

// Получение доступа к полям
echo $item->quantity; // от 1 до 5
echo $item->price;    // от 100 до 1000
echo $item->status;   // 0
```

### Создание с существующими связями

```php
// Использование существующего заказа и товара
$order = MarketplaceOrder::factory()->create();
$marketplaceItem = MarketplaceItem::factory()->create();

$item = MarketplaceOrderItem::factory()->create([
    'marketplace_order_id' => $order->id,
    'marketplace_item_id' => $marketplaceItem->id,
    'quantity' => 10,
    'price' => 500
]);
```

### Создание с назначенными сотрудниками

```php
// Создание позиции с назначенными швеей и закройщиком
$seamstress = User::factory()->create(['role_id' => 3]); // Предполагая роль швеи
$cutter = User::factory()->create(['role_id' => 4]); // Предполагая роль закройщика

$item = MarketplaceOrderItem::factory()->create([
    'seamstress_id' => $seamstress->id,
    'cutter_id' => $cutter->id,
    'status' => 1 // В работе
]);
```

### Массовое создание

```php
// Создание 5 позиций для одного заказа
$order = MarketplaceOrder::factory()->create();
$items = MarketplaceOrderItem::factory()->count(5)->create([
    'marketplace_order_id' => $order->id
]);

// Создание позиций с разным количеством
$items = MarketplaceOrderItem::factory()->count(3)->sequence(
    ['quantity' => 1],
    ['quantity' => 3],
    ['quantity' => 5]
)->create();
```

### Создание с разным статусом

```php
// Создание позиций с разными статусами
$items = MarketplaceOrderItem::factory()->count(5)->sequence(
    ['status' => 0], // Новый
    ['status' => 1], // В работе
    ['status' => 2], // Завершен
    ['status' => 3]  // Отменен
)->create();
```

### В тестах

```php
// Тестирование API
$item = MarketplaceOrderItem::factory()->create();

$response = $this->getJson("/api/order-items/{$item->id}")
    ->assertStatus(200)
    ->assertJson([
        'quantity' => $item->quantity,
        'price' => $item->price,
        'status' => $item->status
    ]);
```

### Комплексные примеры

```php
// Создание полного заказа с несколькими позициями
$order = MarketplaceOrder::factory()->create();

// Создание 3 разных товаров и позиций заказа
$items = collect([1, 2, 3])->map(function ($i) use ($order) {
    return MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'quantity' => rand(1, 5),
        'price' => rand(100, 1000)
    ]);
});

// Создание заказа с этапами выполнения
$item = MarketplaceOrderItem::factory()->create([
    'status' => 1,
    'cutter_id' => User::factory()->create()->id,
    'cutting_completed_at' => now()->subMinutes(30)
]);
```

### Создание с последовательными статусами

```php
// Создание заказа, прошедшего все этапы
$item = MarketplaceOrderItem::factory()->create([
    'status' => 2, // Завершен
    'seamstress_id' => User::factory()->create()->id,
    'cutter_id' => User::factory()->create()->id,
    'cutting_completed_at' => now()->subHours(2),
    'completed_at' => now()->subMinutes(30)
]);
```
