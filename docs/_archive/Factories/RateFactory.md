# RateFactory

## Модель

`App\Models\Rate` - Ставка

## Генерируемые поля и их типы данных

| Поле              | Тип данных | Описание             | Метод генерации                         |
|-------------------|------------|----------------------|-----------------------------------------|
| `user_id`         | integer    | ID пользователя      | `User::factory()`                       |
| `width`           | integer    | Ширина               | `$this->faker->numberBetween(100, 300)` |
| `rate`            | integer    | Ставка               | `$this->faker->numberBetween(100, 500)` |
| `cutter_rate`     | integer    | Ставка закройщика    | `$this->faker->numberBetween(50, 250)`  |
| `not_cutter_rate` | integer    | Ставка не закройщика | `$this->faker->numberBetween(100, 500)` |

## Особые значения и константы

### Диапазоны значений

- `width`: 100-300
- `rate`: 100-500
- `cutter_rate`: 50-250
- `not_cutter_rate`: 100-500

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `User` - создается новая запись пользователя через фабрику

## Примеры использования

### Базовое использование

```php
// Создание ставки с автоматически созданным пользователем
$rate = Rate::factory()->create();

// Получение доступа к полям
echo $rate->width;           // от 100 до 300
echo $rate->rate;            // от 100 до 500
echo $rate->cutter_rate;     // от 50 до 250
echo $rate->not_cutter_rate; // от 100 до 500
```

### Создание с существующим пользователем

```php
// Использование существующего пользователя
$user = User::factory()->create();

$rate = Rate::factory()->create([
    'user_id' => $user->id,
    'width' => 250,
    'rate' => 350
]);
```

### Создание с определенными ставками

```php
// Создание ставки с определенными параметрами
$rate = Rate::factory()->create([
    'width' => 200,
    'rate' => 300,
    'cutter_rate' => 150,
    'not_cutter_rate' => 350
]);
```

### Создание с разной шириной

```php
// Создание ставок для разной ширины материалов
$narrowRate = Rate::factory()->create(['width' => 100]);
$mediumRate = Rate::factory()->create(['width' => 200]);
$wideRate = Rate::factory()->create(['width' => 300]);
```

### Создание с разными ставками для ролей

```php
// Создание ставки с разной оплатой для разных ролей
$rate = Rate::factory()->create([
    'rate' => 400,              // Общая ставка
    'cutter_rate' => 200,       // Ставка для закройщиков
    'not_cutter_rate' => 450    // Ставка для остальных сотрудников
]);
```

### Массовое создание

```php
// Создание 10 ставок
$rates = Rate::factory()->count(10)->create();

// Создание ставок для одного пользователя
$user = User::factory()->create();
$rates = Rate::factory()->count(5)->create(['user_id' => $user->id]);

// Создание ставок с разной шириной
$rates = Rate::factory()->count(3)->sequence(
    ['width' => 100],
    ['width' => 200],
    ['width' => 300]
)->create();
```

### В тестах

```php
// Тестирование API
$rate = Rate::factory()->create();

$response = $this->getJson("/api/rates/{$rate->id}")
    ->assertStatus(200)
    ->assertJson([
        'width' => $rate->width,
        'rate' => $rate->rate,
        'cutter_rate' => $rate->cutter_rate,
        'not_cutter_rate' => $rate->not_cutter_rate
    ]);
```

### Комбинирование с другими фабриками

```php
// Создание пользователя с несколькими ставками
$user = User::factory()->create();
$rates = Rate::factory()->count(5)->create(['user_id' => $user->id]);

// Создание тарифной сетки для отдела
$users = User::factory()->count(3)->create();
foreach ($users as $index => $user) {
    Rate::factory()->create([
        'user_id' => $user->id,
        'width' => 100 + $index * 50,
        'rate' => 200 + $index * 50
    ]);
}
```

### Создание прогрессивной шкалы ставок

```php
// Создание ставок, увеличивающихся с шириной
Rate::factory()->count(5)->sequence(
    ['width' => 100, 'rate' => 200, 'cutter_rate' => 100, 'not_cutter_rate' => 220],
    ['width' => 150, 'rate' => 250, 'cutter_rate' => 125, 'not_cutter_rate' => 275],
    ['width' => 200, 'rate' => 300, 'cutter_rate' => 150, 'not_cutter_rate' => 330],
    ['width' => 250, 'rate' => 350, 'cutter_rate' => 175, 'not_cutter_rate' => 385],
    ['width' => 300, 'rate' => 400, 'cutter_rate' => 200, 'not_cutter_rate' => 440]
)->create();
```

### Создание с предопределенными значениями

```php
// Создание стандартных ставок
$standardRates = [
    ['width' => 100, 'rate' => 150, 'cutter_rate' => 75, 'not_cutter_rate' => 175],
    ['width' => 200, 'rate' => 250, 'cutter_rate' => 125, 'not_cutter_rate' => 275],
    ['width' => 300, 'rate' => 350, 'cutter_rate' => 175, 'not_cutter_rate' => 375],
];

foreach ($standardRates as $rateData) {
    Rate::factory()->create($rateData);
}
```

### Создание для разных категорий сотрудников

```php
// Создание ставок для швей
$seamstresses = User::factory()->count(3)->create(['role_id' => 3]);
foreach ($seamstresses as $seamstress) {
    Rate::factory()->create([
        'user_id' => $seamstress->id,
        'rate' => 300,
        'not_cutter_rate' => 300,
        'cutter_rate' => 150
    ]);
}

// Создание ставок для закройщиков
$cutters = User::factory()->count(2)->create(['role_id' => 4]);
foreach ($cutters as $cutter) {
    Rate::factory()->create([
        'user_id' => $cutter->id,
        'rate' => 350,
        'cutter_rate' => 350,
        'not_cutter_rate' => 200
    ]);
}
```

### Создание с разными диапазонами

```php
// Создание ставок с разными диапазонами значений
$lowRates = Rate::factory()->count(3)->create([
    'rate' => 150,
    'cutter_rate' => 75,
    'not_cutter_rate' => 175
]);

$mediumRates = Rate::factory()->count(3)->create([
    'rate' => 300,
    'cutter_rate' => 150,
    'not_cutter_rate' => 350
]);

$highRates = Rate::factory()->count(3)->create([
    'rate' => 450,
    'cutter_rate' => 225,
    'not_cutter_rate' => 500
]);
```
