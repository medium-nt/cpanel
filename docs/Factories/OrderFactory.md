# OrderFactory

## Модель

`App\Models\Order` - Заказ

## Генерируемые поля и их типы данных

| Поле                   | Тип данных | Описание         | Метод генерации                              |
|------------------------|------------|------------------|----------------------------------------------|
| `type_movement`        | integer    | Тип движения     | `$this->faker->randomElement([4, 7])`        |
| `status`               | integer    | Статус заказа    | `$this->faker->randomElement([0, 1, -1, 3])` |
| `supplier_id`          | integer    | null             | ID поставщика                                | `null` |
| `storekeeper_id`       | integer    | ID кладовщика    | `User::factory()`                            |
| `seamstress_id`        | integer    | ID швеи          | `User::factory()`                            |
| `cutter_id`            | integer    | ID закройщика    | `User::factory()`                            |
| `comment`              | string     | Комментарий      | `$this->faker->sentence`                     |
| `marketplace_order_id` | integer    | null             | ID заказа маркетплейса                       | `null` |
| `is_approved`          | boolean    | Флаг утверждения | `$this->faker->boolean`                      |
| `completed_at`         | datetime   | null             | Время завершения                             | `null` |

## Особые значения и константы

### Типы движения

- `4` - Первый тип движения
- `7` - Второй тип движения

### Статусы заказа

- `0` - Новый
- `1` - В работе
- `-1` - Отменен
- `3` - Завершен

### Значения по умолчанию

- `supplier_id` = `null`
- `marketplace_order_id` = `null`
- `completed_at` = `null`

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `storekeeper_id` - создается новый пользователь (кладовщик) через фабрику
- `seamstress_id` - создается новый пользователь (швея) через фабрику
- `cutter_id` - создается новый пользователь (закройщик) через фабрику

## Примеры использования

### Базовое использование

```php
// Создание заказа с автоматически созданными сотрудниками
$order = Order::factory()->create();

// Получение доступа к полям
echo $order->type_movement;       // 4 или 7
echo $order->status;             // 0, 1, -1 или 3
echo $order->supplier_id;        // null
echo $order->is_approved;        // true или false
echo $order->comment;            // Случайное предложение
echo $order->completed_at;       // null
```

### Создание с определенными параметрами

```php
// Создание заказа с определенными параметрами
$order = Order::factory()->create([
    'type_movement' => 4,
    'status' => 1,
    'is_approved' => true,
    'comment' => 'Срочный заказ'
]);
```

### Создание с существующими пользователями

```php
// Создание пользователей с нужными ролями
$storekeeper = User::factory()->create(['role_id' => 2]); // Кладовщик
$seamstress = User::factory()->create(['role_id' => 3]); // Швея
$cutter = User::factory()->create(['role_id' => 4]); // Закройщик

$order = Order::factory()->create([
    'storekeeper_id' => $storekeeper->id,
    'seamstress_id' => $seamstress->id,
    'cutter_id' => $cutter->id
]);
```

### Создание с поставщиком

```php
// Создание заказа с поставщиком
$supplier = SupplierFactory::new()->create();

$order = Order::factory()->create([
    'supplier_id' => $supplier->id
]);
```

### Создание заказа маркетплейса

```php
// Создание заказа, связанного с маркетплейсом
$marketplaceOrder = MarketplaceOrder::factory()->create();

$order = Order::factory()->create([
    'marketplace_order_id' => $marketplaceOrder->id
]);
```

### Создание с разными статусами

```php
// Создание заказов с разными статусами
$newOrder = Order::factory()->create(['status' => 0]);
$processingOrder = Order::factory()->create(['status' => 1]);
$canceledOrder = Order::factory()->create(['status' => -1]);
$completedOrder = Order::factory()->create([
    'status' => 3,
    'completed_at' => now()->subHours(2)
]);
```

### Массовое создание

```php
// Создание 10 заказов
$orders = Order::factory()->count(10)->create();

// Создание заказов с разным типом движения
$type4Orders = Order::factory()->count(5)->create(['type_movement' => 4]);
$type7Orders = Order::factory()->count(5)->create(['type_movement' => 7]);

// Создание заказов с разными статусами
$orders = Order::factory()->count(10)->create()->each(function ($order) {
    $order->update([
        'status' => $this->faker->randomElement([0, 1, -1, 3])
    ]);
});
```

### Создание с последовательными данными

```php
// Создание заказов с последовательными статусами
Order::factory()->count(4)->sequence(
    ['status' => 0],  // Новый
    ['status' => 1],  // В работе
    ['status' => -1], // Отменен
    ['status' => 3]   // Завершен
)->create();
```

### В тестах

```php
// Тестирование API
$order = Order::factory()->create();

$response = $this->getJson("/api/orders/{$order->id}")
    ->assertStatus(200)
    ->assertJson([
        'type_movement' => $order->type_movement,
        'status' => $order->status,
        'is_approved' => $order->is_approved
    ]);
```

### Комбинирование с другими фабриками

```php
// Создание заказа с движениями материалов
$order = Order::factory()->create();
$materials = Material::factory()->count(3)->create();

foreach ($materials as $material) {
    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id
    ]);
}

// Создание полного заказа с маркетплейсом
$marketplaceOrder = MarketplaceOrder::factory()->create();
$marketplaceItem = MarketplaceItem::factory()->create();
$marketplaceOrderItem = MarketplaceOrderItem::factory()->create([
    'marketplace_order_id' => $marketplaceOrder->id,
    'marketplace_item_id' => $marketplaceItem->id
]);

$order = Order::factory()->create([
    'marketplace_order_id' => $marketplaceOrder->id
]);
```

### Создание с этапами выполнения

```php
// Создание заказа, прошедшего все этапы
$order = Order::factory()->create([
    'status' => 3,
    'is_approved' => true,
    'completed_at' => now()->subMinutes(30)
]);
```

### Создание с фильтруемыми данными

```php
// Создание утвержденных заказов
$approvedOrders = Order::factory()->count(5)->create(['is_approved' => true]);

// Создание неутвержденных заказов
$pendingOrders = Order::factory()->count(5)->create(['is_approved' => false]);

// Создание заказов для конкретного кладовщика
$storekeeper = User::factory()->create();
$storekeeperOrders = Order::factory()->count(5)->create([
    'storekeeper_id' => $storekeeper->id
]);
```

### Создание с временными метками

```php
// Создание завершенных заказов в разное время
$recentOrder = Order::factory()->create([
    'status' => 3,
    'completed_at' => now()->subMinutes(30)
]);

$oldOrder = Order::factory()->create([
    'status' => 3,
    'completed_at' => now()->subDays(7)
]);

$veryOldOrder = Order::factory()->create([
    'status' => 3,
    'completed_at' => now()->subMonths(1)
]);
```
