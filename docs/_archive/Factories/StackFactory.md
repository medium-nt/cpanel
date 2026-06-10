# StackFactory

## Модель

`App\Models\Stack` - Стек (Набор)

## Генерируемые поля и их типы данных

| Поле            | Тип данных | Описание                         | Метод генерации                       |
|-----------------|------------|----------------------------------|---------------------------------------|
| `seamstress_id` | integer    | ID швеи                          | `User::factory()`                     |
| `stack`         | integer    | Текущее количество/значение      | `$this->faker->numberBetween(0, 10)`  |
| `max`           | integer    | Максимальное количество/значение | `$this->faker->numberBetween(11, 20)` |

## Особые значения и константы

### Диапазоны значений

- `stack`: 0-10
- `max`: 11-20 (всегда больше stack по умолчанию)

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `User` - создается новая запись пользователя (предположительно швея) через
  фабрику

## Примеры использования

### Базовое использование

```php
// Создание стека с автоматически созданной швеей
$stack = Stack::factory()->create();

// Получение доступа к полям
echo $stack->seamstress_id; // ID созданного пользователя
echo $stack->stack;         // от 0 до 10
echo $stack->max;           // от 11 до 20
```

### Создание с существующим пользователем

```php
// Использование существующего пользователя
$seamstress = User::factory()->create();

$stack = Stack::factory()->create([
    'seamstress_id' => $seamstress->id,
    'stack' => 5,
    'max' => 15
]);
```

### Создание с определенными значениями

```php
// Создание стека с конкретными значениями
$stack = Stack::factory()->create([
    'stack' => 3,
    'max' => 12
]);

// Создание заполненного стека
$fullStack = Stack::factory()->create([
    'stack' => 10,
    'max' => 10
]);

// Создание пустого стека
$emptyStack = Stack::factory()->create([
    'stack' => 0,
    'max' => 20
]);
```

### Создание с разной загруженностью

```php
// Создание почти полного стека
$nearlyFull = Stack::factory()->create([
    'stack' => 9,
    'max' => 10
]);

// Создание стека на половину
$halfFull = Stack::factory()->create([
    'stack' => 5,
    'max' => 10
]);

// Создание почти пустого стека
$nearlyEmpty = Stack::factory()->create([
    'stack' => 1,
    'max' => 20
]);
```

### Массовое создание

```php
// Создание 10 стеков
$stacks = Stack::factory()->count(10)->create();

// Создание 5 стеков для одной швеи
$seamstress = User::factory()->create();
$stacks = Stack::factory()->count(5)->create(['seamstress_id' => $seamstress->id]);

// Создание стеков с разной загруженностью
$stacks = Stack::factory()->count(3)->sequence(
    ['stack' => 2, 'max' => 10],   // 20% заполнен
    ['stack' => 5, 'max' => 10],   // 50% заполнен
    ['stack' => 9, 'max' => 10]    // 90% заполнен
)->create();
```

### В тестах

```php
// Тестирование API
$stack = Stack::factory()->create();

$response = $this->getJson("/api/stacks/{$stack->id}")
    ->assertStatus(200)
    ->assertJson([
        'seamstress_id' => $stack->seamstress_id,
        'stack' => $stack->stack,
        'max' => $stack->max
    ]);

// Тестирование проверки доступности места
$nearlyFull = Stack::factory()->create(['stack' => 9, 'max' => 10]);
$response = $this->getJson("/api/stacks/{$nearlyFull->id}/available")
    ->assertStatus(200)
    ->assertJson(['available' => 1]);
```

### Комбинирование с другими фабриками

```php
// Создание швеи и её рабочих стеков
$seamstress = User::factory()->create();
$stacks = Stack::factory()->count(3)->create(['seamstress_id' => $seamstress->id]);

// Создание стека и привязка заказов
$stack = Stack::factory()->create();
$orders = Order::factory()->count(5)->create([
    'seamstress_id' => $stack->seamstress_id
]);

// Обновление стека на основе заказов
$stack->update(['stack' => $orders->count()]);
```

### Создание для анализа нагрузки

```php
// Создание данных для анализа нагрузки швей
$seamstresses = User::factory()->count(5)->create();
$stacks = collect();

foreach ($seamstresses as $seamstress) {
    // Создаем стеки с разной загруженностью для анализа
    $loadPercentage = rand(20, 100);
    $maxCapacity = rand(10, 20);
    $currentStack = intval($maxCapacity * ($loadPercentage / 100));

    $stacks->push(Stack::factory()->create([
        'seamstress_id' => $seamstress->id,
        'stack' => $currentStack,
        'max' => $maxCapacity
    ]));
}
```

### Создание для моделирования приоритетов

```php
// Создание стеков с разными уровнями приоритета
$highPriorityStack = Stack::factory()->create([
    'stack' => 8,
    'max' => 10
]);

$mediumPriorityStack = Stack::factory()->create([
    'stack' => 5,
    'max' => 10
]);

$lowPriorityStack = Stack::factory()->create([
    'stack' => 2,
    'max' => 10
]);
```

### Создание с предопределенными значениями

```php
// Создание стандартных конфигураций стеков
$standardStacks = [
    ['stack' => 0, 'max' => 10],   // Пустой
    ['stack' => 5, 'max' => 10],   // На половину
    ['stack' => 10, 'max' => 10],  // Полный
    ['stack' => 15, 'max' => 20],  // Средняя загруженность
    ['stack' => 20, 'max' => 20],  // Максимальная загруженность
];

foreach ($standardStacks as $stackData) {
    Stack::factory()->create($stackData);
}
```

### Создание с бизнес-логикой

```php
// Создание стеков для управления рабочей нагрузкой
$seamstress = User::factory()->create();

// Создаем стек для ежедневных задач
$dailyStack = Stack::factory()->create([
    'seamstress_id' => $seamstress->id,
    'stack' => 5,
    'max' => 8
]);

// Создаем стек для срочных задач
$urgentStack = Stack::factory()->create([
    'seamstress_id' => $seamstress->id,
    'stack' => 2,
    'max' => 3
]);

// Создаем стек для backlog
$backlogStack = Stack::factory()->create([
    'seamstress_id' => $seamstress->id,
    'stack' => 15,
    'max' => 50
]);
```

### Создание с прогрессией

```php
// Создание стеков, показывающих прогрессию
$seamstress = User::factory()->create();

$stacks = collect();
for ($i = 1; $i <= 5; $i++) {
    $stacks->push(Stack::factory()->create([
        'seamstress_id' => $seamstress->id,
        'stack' => $i * 2,
        'max' => 10
    ]));
}
```

### Создание с использованием callback

```php
// Создание стека с кастомной логикой
Stack::factory()->create([
    'seamstress_id' => function () {
        return User::factory()->create(['role_id' => 3])->id; // Предполагая, что 3 - это роль швеи
    },
    'stack' => function (array $attributes) {
        // Стек всегда меньше максимального значения
        return rand(0, $attributes['max'] - 1);
    },
    'max' => 15
]);
```
