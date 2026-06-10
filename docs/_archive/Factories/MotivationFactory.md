# MotivationFactory

## Модель

`App\Models\Motivation` - Мотивация сотрудника

## Генерируемые поля и их типы данных

| Поле               | Тип данных | Описание              | Метод генерации                       |
|--------------------|------------|-----------------------|---------------------------------------|
| `user_id`          | integer    | ID пользователя       | `User::factory()`                     |
| `from`             | integer    | Начало диапазона (от) | `$this->faker->numberBetween(0, 10)`  |
| `to`               | integer    | Конец диапазона (до)  | `$this->faker->numberBetween(11, 20)` |
| `bonus`            | integer    | Бонус                 | `$this->faker->numberBetween(10, 50)` |
| `cutter_bonus`     | integer    | Бонус закройщика      | `$this->faker->numberBetween(10, 50)` |
| `not_cutter_bonus` | integer    | Бонус не закройщика   | `$this->faker->numberBetween(10, 50)` |

## Особые значения и константы

### Диапазоны значений

- `from`: 0-10
- `to`: 11-20 (всегда больше from по умолчанию)

### Бонусы

- `bonus`: 10-50
- `cutter_bonus`: 10-50
- `not_cutter_bonus`: 10-50

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `User` - создается новая запись пользователя через фабрику

## Примеры использования

### Базовое использование

```php
// Создание мотивации с автоматически созданным пользователем
$motivation = Motivation::factory()->create();

// Получение доступа к полям
echo $motivation->from;            // от 0 до 10
echo $motivation->to;              // от 11 до 20
echo $motivation->bonus;           // от 10 до 50
echo $motivation->cutter_bonus;    // от 10 до 50
echo $motivation->not_cutter_bonus; // от 10 до 50
```

### Создание с существующим пользователем

```php
// Использование существующего пользователя
$user = User::factory()->create();

$motivation = Motivation::factory()->create([
    'user_id' => $user->id,
    'from' => 5,
    'to' => 15,
    'bonus' => 30
]);
```

### Создание с определенными диапазонами

```php
// Создание мотивации для разных диапазонов производительности
$motivation1 = Motivation::factory()->create([
    'from' => 0,
    'to' => 5,
    'bonus' => 10
]);

$motivation2 = Motivation::factory()->create([
    'from' => 6,
    'to' => 10,
    'bonus' => 20
]);

$motivation3 = Motivation::factory()->create([
    'from' => 11,
    'to' => 20,
    'bonus' => 50
]);
```

### Создание с разными бонусами для ролей

```php
// Создание мотивации с разными бонусами
$motivation = Motivation::factory()->create([
    'cutter_bonus' => 40,      // Высокий бонус для закройщиков
    'not_cutter_bonus' => 20,  // Стандартный бонус для остальных
]);
```

### Массовое создание

```php
// Создание 5 мотиваций для разных пользователей
$motivations = Motivation::factory()->count(5)->create();

// Создание мотиваций для одного пользователя с разными диапазонами
$user = User::factory()->create();
$motivations = Motivation::factory()->count(3)->create([
    'user_id' => $user->id
])->sequence(
    ['from' => 0, 'to' => 5],
    ['from' => 6, 'to' => 10],
    ['from' => 11, 'to' => 20]
);
```

### В тестах

```php
// Тестирование API
$motivation = Motivation::factory()->create();

$response = $this->getJson("/api/motivations/{$motivation->id}")
    ->assertStatus(200)
    ->assertJson([
        'from' => $motivation->from,
        'to' => $motivation->to,
        'bonus' => $motivation->bonus,
        'cutter_bonus' => $motivation->cutter_bonus,
        'not_cutter_bonus' => $motivation->not_cutter_bonus
    ]);
```

### Комбинирование с другими фабриками

```php
// Создание пользователя с его мотивацией
$user = User::factory()->create();
$motivation = Motivation::factory()->create(['user_id' => $user->id]);

// Создание нескольких мотиваций для пользователя
$user = User::factory()->create();
$motivations = collect([
    Motivation::factory()->make(['user_id' => $user->id, 'from' => 0, 'to' => 5]),
    Motivation::factory()->make(['user_id' => $user->id, 'from' => 6, 'to' => 10]),
    Motivation::factory()->make(['user_id' => $user->id, 'from' => 11, 'to' => 20])
]);

// Создание полной системы мотивации
$users = User::factory()->count(3)->create();
foreach ($users as $index => $user) {
    Motivation::factory()->create([
        'user_id' => $user->id,
        'from' => $index * 5,
        'to' => ($index + 1) * 5,
        'bonus' => 10 + $index * 10
    ]);
}
```

### Создание иерархической системы мотивации

```php
// Создание мотивации с увеличивающимися бонусами
Motivation::factory()->count(5)->create()->each(function ($motivation, $index) {
    $motivation->update([
        'from' => $index * 4,
        'to' => ($index + 1) * 4,
        'bonus' => 10 + $index * 10,
        'cutter_bonus' => 15 + $index * 10,
        'not_cutter_bonus' => 5 + $index * 5
    ]);
});

// Создание мотивации с пересекающимися диапазонами
$user = User::factory()->create();
Motivation::factory()->count(3)->create([
    'user_id' => $user->id
])->sequence(
    ['from' => 0, 'to' => 5, 'bonus' => 10],
    ['from' => 3, 'to' => 8, 'bonus' => 20],
    ['from' => 7, 'to' => 15, 'bonus' => 30]
]);
```

### Создание с предопределенными значениями

```php
// Создание стандартных уровней мотивации
$standardMotivations = [
    ['from' => 0, 'to' => 5, 'bonus' => 10, 'cutter_bonus' => 15, 'not_cutter_bonus' => 5],
    ['from' => 6, 'to' => 10, 'bonus' => 20, 'cutter_bonus' => 25, 'not_cutter_bonus' => 15],
    ['from' => 11, 'to' => 20, 'bonus' => 30, 'cutter_bonus' => 40, 'not_cutter_bonus' => 20],
];

foreach ($standardMotivations as $motivationData) {
    Motivation::factory()->create($motivationData);
}
```
