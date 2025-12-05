# MovementMaterialFactory

## Модель

`App\Models\MovementMaterial` - Движение материала

## Генерируемые поля и их типы данных

| Поле               | Тип данных | Описание              | Метод генерации                          |
|--------------------|------------|-----------------------|------------------------------------------|
| `material_id`      | integer    | ID материала          | `Material::factory()`                    |
| `order_id`         | integer    | ID заказа             | `Order::factory()`                       |
| `quantity`         | integer    | Количество            | `$this->faker->numberBetween(1, 100)`    |
| `ordered_quantity` | integer    | Заказанное количество | `$this->faker->numberBetween(1, 100)`    |
| `price`            | float      | Цена                  | `$this->faker->randomFloat(2, 10, 1000)` |

## Особые значения и константы

### Диапазоны значений

- `quantity`: 1-100
- `ordered_quantity`: 1-100
- `price`: 10.00-1000.00 (с 2 знаками после запятой)

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `Material` - создается новая запись материала через фабрику
- `Order` - создается новая запись заказа через фабрику

## Примеры использования

### Базовое использование

```php
// Создание движения материала с автоматически созданным материалом и заказом
$movement = MovementMaterial::factory()->create();

// Получение доступа к полям
echo $movement->quantity;          // от 1 до 100
echo $movement->ordered_quantity;  // от 1 до 100
echo $movement->price;             // от 10.00 до 1000.00
```

### Создание с существующими связями

```php
// Использование существующего материала и заказа
$material = Material::factory()->create();
$order = Order::factory()->create();

$movement = MovementMaterial::factory()->create([
    'material_id' => $material->id,
    'order_id' => $order->id,
    'quantity' => 50,
    'price' => 250.50
]);
```

### Создание с определенными количествами

```php
// Создание движения с определенными параметрами
$movement = MovementMaterial::factory()->create([
    'quantity' => 75,
    'ordered_quantity' => 100,
    'price' => 500.00
]);
```

### Создание с большим количеством

```php
// Создание движения с большим количеством материала
$movement = MovementMaterial::factory()->create([
    'quantity' => 100,
    'ordered_quantity' => 150,
    'price' => 999.99
]);
```

### Массовое создание

```php
// Создание 10 движений материалов
$movements = MovementMaterial::factory()->count(10)->create();

// Создание 5 движений для одного материала
$material = Material::factory()->create();
$movements = MovementMaterial::factory()->count(5)->create([
    'material_id' => $material->id
]);

// Создание движений для одного заказа
$order = Order::factory()->create();
$movements = MovementMaterial::factory()->count(3)->create([
    'order_id' => $order->id
]);
```

### Создание с последовательными значениями

```php
// Создание движений с увеличивающимся количеством
MovementMaterial::factory()->count(5)->sequence(
    ['quantity' => 10, 'ordered_quantity' => 10],
    ['quantity' => 20, 'ordered_quantity' => 25],
    ['quantity' => 30, 'ordered_quantity' => 35],
    ['quantity' => 40, 'ordered_quantity' => 45],
    ['quantity' => 50, 'ordered_quantity' => 60]
)->create();
```

### В тестах

```php
// Тестирование API
$movement = MovementMaterial::factory()->create();

$response = $this->getJson("/api/material-movements/{$movement->id}")
    ->assertStatus(200)
    ->assertJson([
        'quantity' => $movement->quantity,
        'ordered_quantity' => $movement->ordered_quantity,
        'price' => $movement->price
    ]);
```

### Комбинирование с другими фабриками

```php
// Создание полного заказа с движениями материалов
$order = Order::factory()->create();
$materials = Material::factory()->count(3)->create();

foreach ($materials as $material) {
    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id
    ]);
}

// Создание материала с историей движений
$material = Material::factory()->create();
$movements = MovementMaterial::factory()->count(5)->create([
    'material_id' => $material->id
]);
```

### Создание с дефицитом/избытком

```php
// Создание с дефицитом (ordered > quantity)
$shortage = MovementMaterial::factory()->create([
    'quantity' => 80,
    'ordered_quantity' => 100
]);

// Создание с избытком (quantity > ordered)
$surplus = MovementMaterial::factory()->create([
    'quantity' => 100,
    'ordered_quantity' => 80
]);

// Создание с идеальным соответствием
$perfect = MovementMaterial::factory()->create([
    'quantity' => 100,
    'ordered_quantity' => 100
]);
```

### Создание с разной ценой

```php
// Создание движений с разными ценовыми категориями
$lowPrice = MovementMaterial::factory()->create(['price' => 25.50]);
$mediumPrice = MovementMaterial::factory()->create(['price' => 150.75]);
$highPrice = MovementMaterial::factory()->create(['price' => 750.00]);

// Или создать набор с разными ценами
$movements = MovementMaterial::factory()->count(3)->sequence(
    ['price' => 10.00],
    ['price' => 100.00],
    ['price' => 1000.00]
)->create();
```

### Создание для анализа использования материалов

```php
// Создание данных для анализа использования материалов по заказам
$orders = Order::factory()->count(5)->create();
$materials = Material::factory()->count(3)->create();

foreach ($orders as $order) {
    // Каждый заказ использует случайное подмножество материалов
    $usedMaterials = $materials->random(rand(1, 3));
    foreach ($usedMaterials as $material) {
        MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $material->id,
            'quantity' => rand(10, 50),
            'ordered_quantity' => rand(10, 50)
        ]);
    }
}
```

### Создание с фиксированными значениями

```php
// Создание стандартных движений
$standardMovements = [
    ['quantity' => 10, 'ordered_quantity' => 10, 'price' => 100.00],
    ['quantity' => 25, 'ordered_quantity' => 30, 'price' => 250.00],
    ['quantity' => 50, 'ordered_quantity' => 45, 'price' => 500.00],
];

foreach ($standardMovements as $movementData) {
    MovementMaterial::factory()->create($movementData);
}
```
