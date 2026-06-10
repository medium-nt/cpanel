# ScheduleFactory

## Модель

`App\Models\Schedule` - График работы

## Генерируемые поля и их типы данных

| Поле      | Тип данных | Описание        | Метод генерации        |
|-----------|------------|-----------------|------------------------|
| `user_id` | integer    | ID пользователя | `User::factory()`      |
| `date`    | date       | Дата            | `$this->faker->date()` |

## Особые значения и константы

### Формат даты

Используется стандартный формат Faker для даты (Y-m-d)

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `User` - создается новая запись пользователя через фабрику

## Примеры использования

### Базовое использование

```php
// Создание записи в графике с автоматически созданным пользователем
$schedule = Schedule::factory()->create();

// Получение доступа к полям
echo $schedule->user_id; // ID созданного пользователя
echo $schedule->date;    // Случайная дата в формате Y-m-d
```

### Создание с существующим пользователем

```php
// Использование существующего пользователя
$user = User::factory()->create();

$schedule = Schedule::factory()->create([
    'user_id' => $user->id,
    'date' => '2024-12-25'
]);
```

### Создание с определенной датой

```php
// Создание записи на конкретную дату
$schedule = Schedule::factory()->create([
    'date' => '2024-12-31'
]);

// Создание на сегодня
$schedule = Schedule::factory()->create([
    'date' => now()->toDateString()
]);

// Создание на завтра
$schedule = Schedule::factory()->create([
    'date' => now()->addDay()->toDateString()
]);
```

### Создание графика на период

```php
// Создание графика на неделю
$user = User::factory()->create();
for ($i = 0; $i < 7; $i++) {
    Schedule::factory()->create([
        'user_id' => $user->id,
        'date' => now()->startOfWeek()->addDays($i)->toDateString()
    ]);
}

// Создание графика на месяц
$user = User::factory()->create();
for ($i = 0; $i < 30; $i++) {
    Schedule::factory()->create([
        'user_id' => $user->id,
        'date' => now()->startOfMonth()->addDays($i)->toDateString()
    ]);
}
```

### Массовое создание

```php
// Создание 10 записей в графике
$schedules = Schedule::factory()->count(10)->create();

// Создание 5 записей для одного пользователя
$user = User::factory()->create();
$schedules = Schedule::factory()->count(5)->create(['user_id' => $user->id]);
```

### Создание с последовательными датами

```php
// Создание записей с последовательными датами
Schedule::factory()->count(5)->sequence(
    ['date' => now()->toDateString()],
    ['date' => now()->addDay()->toDateString()],
    ['date' => now()->addDays(2)->toDateString()],
    ['date' => now()->addDays(3)->toDateString()],
    ['date' => now()->addDays(4)->toDateString()]
)->create();
```

### В тестах

```php
// Тестирование API
$schedule = Schedule::factory()->create();

$response = $this->getJson("/api/schedules/{$schedule->id}")
    ->assertStatus(200)
    ->assertJson([
        'user_id' => $schedule->user_id,
        'date' => $schedule->date
    ]);

// Тестирование фильтрации по дате
$today = now()->toDateString();
$todaySchedule = Schedule::factory()->create(['date' => $today]);

$response = $this->getJson("/api/schedules?date={$today}")
    ->assertStatus(200)
    ->assertJsonCount(1);
```

### Комбинирование с другими фабриками

```php
// Создание пользователя и его графика работы
$user = User::factory()->create();
$schedules = Schedule::factory()->count(5)->create(['user_id' => $user->id]);

// Создание графика для нескольких сотрудников
$users = User::factory()->count(3)->create();
foreach ($users as $user) {
    // График на следующую неделю для каждого сотрудника
    for ($i = 0; $i < 7; $i++) {
        Schedule::factory()->create([
            'user_id' => $user->id,
            'date' => now()->next('monday')->addDays($i)->toDateString()
        ]);
    }
}
```

### Создание графика с выходными

```php
// Создание графика работы только на будни (без выходных)
$user = User::factory()->create();
$currentDate = now()->startOfWeek();

for ($i = 0; $i < 5; $i++) { // Только 5 будних дней
    Schedule::factory()->create([
        'user_id' => $user->id,
        'date' => $currentDate->addDays($i)->toDateString()
    ]);
}

// Пропуская выходные
$currentDate = now()->startOfWeek();
for ($i = 0; $i < 7; $i++) {
    $date = $currentDate->addDays($i);
    if ($date->dayOfWeek < 5) { // 0-4 = Пн-Пт
        Schedule::factory()->create([
            'user_id' => $user->id,
            'date' => $date->toDateString()
        ]);
    }
}
```

### Создание с разными временными периодами

```php
// Создание записей в прошлом
$pastSchedule = Schedule::factory()->create([
    'date' => now()->subDays(10)->toDateString()
]);

// Создание записей в будущем
$futureSchedule = Schedule::factory()->create([
    'date' => now()->addDays(10)->toDateString()
]);

// Создание записей на разные месяцы
Schedule::factory()->count(3)->sequence(
    ['date' => now()->startOfMonth()->toDateString()],
    ['date' => now()->addMonth()->startOfMonth()->toDateString()],
    ['date' => now()->addMonths(2)->startOfMonth()->toDateString()]
)->create();
```

### Создание для анализа посещаемости

```php
// Создание данных для анализа посещаемости
$users = User::factory()->count(5)->create();
$startDate = now()->subMonth();
$endDate = now();

$currentDate = $startDate->copy();
while ($currentDate <= $endDate) {
    // Не создаем записи на выходные
    if ($currentDate->dayOfWeek < 5) {
        // Случайные сотрудники работают в случайные дни
        $workingUsers = $users->random(rand(2, 5));
        foreach ($workingUsers as $user) {
            Schedule::factory()->create([
                'user_id' => $user->id,
                'date' => $currentDate->toDateString()
            ]);
        }
    }
    $currentDate->addDay();
}
```

### Создание с предопределенными датами

```php
// Создание записей на праздники
$holidays = [
    '2024-01-01', // Новый год
    '2024-02-23', // 23 февраля
    '2024-03-08', // 8 марта
    '2024-05-09', // День победы
];

foreach ($holidays as $date) {
    Schedule::factory()->create(['date' => $date]);
}
```
