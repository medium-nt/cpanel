# Модель Transaction

**Путь:** `app/Models/Transaction.php`

**Назначение:** Модель для хранения финансовых транзакций и начислений
сотрудникам

**Таблица в БД:** `transactions`

## Атрибуты

### Fillable (массово назначаемые)

- `user_id` - ID пользователя (сотрудника)
- `title` - Название/описание транзакции
- `marketplace_order_item_id` - ID элемента заказа (связь с выполненной работой)
- `accrual_for_date` - Дата, за которую производится начисление
- `amount` - Сумма транзакции
- `status` - Статус транзакции
- `transaction_type` - Тип транзакции
- `paid_at` - Дата/время выплаты
- `is_bonus` - Флаг, является ли транзакция бонусом

## Связи (Relationships)

### BelongsTo (принадлежит к)

- `user()` - Пользователь (сотрудник), которому принадлежит транзакция

## Особенности использования

1. **Финансовый учет:** Используется для отслеживания всех финансовых операций с
   сотрудниками
2. **Привязка к заказам:** Транзакции могут быть привязаны к конкретным
   элементам заказов
3. **Типы транзакций:** Поддерживает различные типы транзакций (зарплата,
   бонусы, штрафы и т.д.)
4. **Статусы:** Имеет систему статусов для отслеживания состояния транзакции (
   создана, выплачена и т.д.)
5. **Бонусы:** Поддерживает флаг `is_bonus` для分离 бонусных начислений
6. **Дата начисления:** Поле `accrual_for_date` позволяет отслеживать, за какой
   период производится начисление

## Примеры использования

```php
// Получение транзакций пользователя
$transactions = Transaction::where('user_id', $userId)->get();

// Получение транзакций за период
$monthlyTransactions = Transaction::whereBetween('accrual_for_date', [$startOfMonth, $endOfMonth])->get();

// Фильтрация по статусу
$unpaidTransactions = Transaction::where('status', 'pending')->get();

// Получение бонусных начислений
$bonuses = Transaction::where('is_bonus', true)->get();

// Привязка к элементу заказа
$transaction = Transaction::create([
    'user_id' => $userId,
    'title' => 'Начисление за заказ #123',
    'marketplace_order_item_id' => $orderItemId,
    'accrual_for_date' => now()->format('Y-m-d'),
    'amount' => 500.00,
    'status' => 'pending',
    'transaction_type' => 'salary',
    'is_bonus' => false
]);

// Получение транзакций с пользователями
$transactions = Transaction::with('user')->get();

// Выплата транзакции
$transaction->update([
    'status' => 'paid',
    'paid_at' => now()
]);

// Расчет общей суммы к выплате
$totalToPay = Transaction::where('status', 'pending')
    ->where('user_id', $userId)
    ->sum('amount');
```
