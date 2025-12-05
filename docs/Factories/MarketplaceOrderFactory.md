# MarketplaceOrderFactory

## Модель

`App\Models\MarketplaceOrder` - Заказ маркетплейса

## Генерируемые поля и их типы данных

| Поле               | Тип данных | Описание                        | Метод генерации                                  |
|--------------------|------------|---------------------------------|--------------------------------------------------|
| `order_id`         | string     | Уникальный идентификатор заказа | `$this->faker->unique()->numerify('##########')` |
| `marketplace_id`   | integer    | ID маркетплейса                 | `$this->faker->randomElement([1, 2])`            |
| `fulfillment_type` | string     | Тип выполнения заказа           | `$this->faker->randomElement(['FBS', 'FBO'])`    |
| `created_at`       | datetime   | Дата создания заказа            | `$this->faker->dateTimeThisMonth()`              |
| `status`           | integer    | Статус заказа                   | `0`                                              |

## Особые значения и константы

### ID маркетплейсов

- `1` - OZON
- `2` - WB (Wildberries)

### Типы выполнения заказа (Fulfillment)

- `FBS` - Fulfillment by Seller (продавец сам выполняет заказ)
- `FBO` - Fulfillment by Operator (оператор выполняет заказ)

### Статус заказа

- `0` - статус по умолчанию

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание заказа маркетплейса
$order = MarketplaceOrder::factory()->create();

// Получение доступа к полям
echo $order->order_id;         // Уникальный ID из 10 цифр
echo $order->marketplace_id;   // 1 (OZON) или 2 (WB)
echo $order->fulfillment_type; // 'FBS' или 'FBO'
echo $order->status;           // 0
```

### Создание с определенными параметрами

```php
// Создание заказа для OZON
$order = MarketplaceOrder::factory()->create([
    'marketplace_id' => 1,
    'order_id' => '1234567890',
    'fulfillment_type' => 'FBS'
]);

// Создание заказа для WB с типом FBO
$order = MarketplaceOrder::factory()->create([
    'marketplace_id' => 2,
    'fulfillment_type' => 'FBO'
]);

// Создание заказа с определенным статусом
$order = MarketplaceOrder::factory()->create([
    'status' => 1, // Например, статус "В работе"
]);
```

### Массовое создание

```php
// Создание 10 заказов
$orders = MarketplaceOrder::factory()->count(10)->create();

// Создание заказов для разных маркетплейсов
$ozonOrders = MarketplaceOrder::factory()->count(5)->create(['marketplace_id' => 1]);
$wbOrders = MarketplaceOrder::factory()->count(5)->create(['marketplace_id' => 2]);

// Создание заказов с разными типами выполнения
$fbsOrders = MarketplaceOrder::factory()->count(5)->create(['fulfillment_type' => 'FBS']);
$fboOrders = MarketplaceOrder::factory()->count(5)->create(['fulfillment_type' => 'FBO']);
```

### В тестах

```php
// Тестирование API
$order = MarketplaceOrder::factory()->create();

$response = $this->getJson("/api/marketplace-orders/{$order->id}")
    ->assertStatus(200)
    ->assertJson([
        'order_id' => $order->order_id,
        'marketplace_id' => $order->marketplace_id,
        'fulfillment_type' => $order->fulfillment_type,
        'status' => 0
    ]);
```

### Комбинирование с другими фабриками

```php
// Создание заказа с товарами
$order = MarketplaceOrder::factory()->create();
$orderItems = MarketplaceOrderItemFactory::new()->count(3)->create([
    'marketplace_order_id' => $order->id
]);

// Создание заказа и связь с элементом маркетплейса
$item = MarketplaceItemFactory::new()->create();
$order = MarketplaceOrderFactory::new()->create([
    'marketplace_item_id' => $item->id
]);
```

### Создание наборов заказов

```php
// Создание заказов для разных маркетплейсов с разными типами
$orders = collect([
    // OZON заказы
    ...MarketplaceOrder::factory()->count(3)->create([
        'marketplace_id' => 1,
        'fulfillment_type' => 'FBS'
    ]),
    ...MarketplaceOrder::factory()->count(3)->create([
        'marketplace_id' => 1,
        'fulfillment_type' => 'FBO'
    ]),
    // WB заказы
    ...MarketplaceOrder::factory()->count(3)->create([
        'marketplace_id' => 2,
        'fulfillment_type' => 'FBS'
    ]),
    ...MarketplaceOrder::factory()->count(3)->create([
        'marketplace_id' => 2,
        'fulfillment_type' => 'FBO'
    ]),
]);

// Создание заказов с уникальными order_id
$orderIds = ['1000000001', '1000000002', '1000000003'];
foreach ($orderIds as $orderId) {
    MarketplaceOrder::factory()->create([
        'order_id' => $orderId,
        'marketplace_id' => $this->faker->randomElement([1, 2])
    ]);
}
```
