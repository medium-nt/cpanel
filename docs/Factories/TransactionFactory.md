# TransactionFactory

## Модель

`App\Models\Transaction` - Транзакция

## Генерируемые поля и их типы данных

| Поле               | Тип данных | Описание            | Метод генерации                              |
|--------------------|------------|---------------------|----------------------------------------------|
| `user_id`          | integer    | ID пользователя     | `User::factory()`                            |
| `title`            | string     | Название транзакции | `$this->faker->sentence`                     |
| `accrual_for_date` | date       | Дата начисления     | `$this->faker->date()`                       |
| `amount`           | integer    | Сумма               | `$this->faker->numberBetween(100, 1000)`     |
| `transaction_type` | string     | Тип транзакции      | `$this->faker->randomElement(['in', 'out'])` |
| `status`           | integer    | Статус              | `$this->faker->randomElement([0, 1, 2])`     |
| `is_bonus`         | boolean    | Флаг бонуса         | `$this->faker->boolean`                      |
| `paid_at`          | datetime   | null                | Время оплаты                                 | `null` |

## Особые значения и константы

### Типы транзакций

- `in` - Входящая транзакция
- `out` - Исходящая транзакция

### Статусы транзакций

- `0` - Первый статус
- `1` - Второй статус
- `2` - Третий статус

### Диапазоны значений

- `amount`: 100-1000

### Значения по умолчанию

- `paid_at` = `null`

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

### Автоматически создаваемые связи

- `User` - создается новая запись пользователя через фабрику

## Примеры использования

### Базовое использование

```php
// Создание транзакции с автоматически созданным пользователем
$transaction = Transaction::factory()->create();

// Получение доступа к полям
echo $transaction->user_id;            // ID созданного пользователя
echo $transaction->title;              // Случайное предложение
echo $transaction->accrual_for_date;   // Случайная дата
echo $transaction->amount;             // от 100 до 1000
echo $transaction->transaction_type;   // 'in' или 'out'
echo $transaction->status;             // 0, 1 или 2
echo $transaction->is_bonus;           // true или false
echo $transaction->paid_at;            // null
```

### Создание с существующим пользователем

```php
// Использование существующего пользователя
$user = User::factory()->create();

$transaction = Transaction::factory()->create([
    'user_id' => $user->id,
    'title' => 'Премия за перевыполнение плана',
    'amount' => 5000
]);
```

### Создание с определенными параметрами

```php
// Создание входящей транзакции
$incomeTransaction = Transaction::factory()->create([
    'transaction_type' => 'in',
    'title' => 'Зарплата',
    'amount' => 30000,
    'status' => 1
]);

// Создание исходящей транзакции
$expenseTransaction = Transaction::factory()->create([
    'transaction_type' => 'out',
    'title' => 'Штраф за опоздание',
    'amount' => 500,
    'status' => 2
]);

// Создание бонусной транзакции
$bonusTransaction = Transaction::factory()->create([
    'is_bonus' => true,
    'title' => 'Бонус за качество работы',
    'amount' => 2000
]);
```

### Создание оплаченных транзакций

```php
// Создание оплаченной транзакции
$paidTransaction = Transaction::factory()->create([
    'status' => 2,
    'paid_at' => now()->subDays(2)
]);

// Создание транзакции с разным статусом оплаты
$unpaid = Transaction::factory()->create(['status' => 0, 'paid_at' => null]);
$processing = Transaction::factory()->create(['status' => 1, 'paid_at' => null]);
$paid = Transaction::factory()->create(['status' => 2, 'paid_at' => now()]);
```

### Массовое создание

```php
// Создание 10 транзакций
$transactions = Transaction::factory()->count(10)->create();

// Создание 5 транзакций для одного пользователя
$user = User::factory()->create();
$transactions = Transaction::factory()->count(5)->create(['user_id' => $user->id]);

// Создание транзакций разных типов
$incomeTransactions = Transaction::factory()->count(5)->create(['transaction_type' => 'in']);
$expenseTransactions = Transaction::factory()->count(5)->create(['transaction_type' => 'out']);
```

### Создание с использованием sequence

```php
// Создание транзакций с последовательными данными
Transaction::factory()->count(3)->sequence(
    ['title' => 'Зарплата', 'amount' => 30000, 'transaction_type' => 'in'],
    ['title' => 'Аванса', 'amount' => 15000, 'transaction_type' => 'in'],
    ['title' => 'Штраф', 'amount' => 500, 'transaction_type' => 'out']
)->create();
```

### В тестах

```php
// Тестирование API
$transaction = Transaction::factory()->create();

$response = $this->getJson("/api/transactions/{$transaction->id}")
    ->assertStatus(200)
    ->assertJson([
        'user_id' => $transaction->user_id,
        'title' => $transaction->title,
        'amount' => $transaction->amount,
        'transaction_type' => $transaction->transaction_type,
        'status' => $transaction->status,
        'is_bonus' => $transaction->is_bonus
    ]);

// Тестирование фильтрации по типу
$response = $this->getJson("/api/transactions?type=in")
    ->assertStatus(200);

// Тестирование фильтрации по статусу
$response = $this->getJson("/api/transactions?status=2")
    ->assertStatus(200);
```

### Комбинирование с другими фабриками

```php
// Создание пользователя и его транзакций
$user = User::factory()->create();
$transactions = Transaction::factory()->count(10)->create(['user_id' => $user->id]);

// Создание транзакций для разных пользователей
$users = User::factory()->count(3)->create();
foreach ($users as $user) {
    Transaction::factory()->count(5)->create(['user_id' => $user->id]);
}

// Создание заказов и связанных транзакций
$order = Order::factory()->create();
$transaction = Transaction::factory()->create([
    'user_id' => $order->seamstress_id,
    'title' => "Оплата за заказ №{$order->id}",
    'accrual_for_date' => $order->completed_at ?? now()
]);
```

### Создание для анализа финансов

```php
// Создание данных для анализа доходов и расходов
$user = User::factory()->create();

// Транзакции за последний месяц
for ($i = 0; $i < 30; $i++) {
    Transaction::factory()->create([
        'user_id' => $user->id,
        'accrual_for_date' => now()->subDays($i)->toDateString(),
        'transaction_type' => rand(0, 1) ? 'in' : 'out',
        'amount' => rand(100, 5000),
        'status' => rand(1, 2)
    ]);
}
```

### Создание с бизнес-логикой

```php
// Создание транзакций для расчета зарплаты
$seamstress = User::factory()->create();

// Базовая зарплата
Transaction::factory()->create([
    'user_id' => $seamstress->id,
    'title' => 'Базовая зарплата',
    'amount' => 20000,
    'transaction_type' => 'in',
    'status' => 2,
    'paid_at' => now()
]);

// Премия
Transaction::factory()->create([
    'user_id' => $seamstress->id,
    'title' => 'Премия за качество',
    'amount' => 5000,
    'transaction_type' => 'in',
    'is_bonus' => true,
    'status' => 2,
    'paid_at' => now()
]);

// Штрафы
for ($i = 0; $i < 3; $i++) {
    Transaction::factory()->create([
        'user_id' => $seamstress->id,
        'title' => 'Штраф за брак',
        'amount' => 500,
        'transaction_type' => 'out',
        'status' => 2,
        'paid_at' => now()
    ]);
}
```

### Создание с временными метками

```php
// Создание транзакций в разном времени
$recent = Transaction::factory()->create([
    'paid_at' => now()->subMinutes(30)
]);

$old = Transaction::factory()->create([
    'paid_at' => now()->subDays(7)
]);

$veryOld = Transaction::factory()->create([
    'paid_at' => now()->subMonths(1)
]);

// Создание транзакций на будущие даты
$futureTransaction = Transaction::factory()->create([
    'accrual_for_date' => now()->addDays(15)->toDateString(),
    'status' => 0 // Не оплачена
]);
```

### Создание с предопределенными значениями

```php
// Создание стандартных типов транзакций
$standardTransactions = [
    [
        'title' => 'Оклад',
        'transaction_type' => 'in',
        'is_bonus' => false,
        'status' => 1
    ],
    [
        'title' => 'Премия',
        'transaction_type' => 'in',
        'is_bonus' => true,
        'status' => 1
    ],
    [
        'title' => 'Штраф',
        'transaction_type' => 'out',
        'is_bonus' => false,
        'status' => 2
    ],
    [
        'title' => 'Аванс',
        'transaction_type' => 'in',
        'is_bonus' => false,
        'status' => 2
    ]
];

foreach ($standardTransactions as $transactionData) {
    Transaction::factory()->create($transactionData);
}
```
