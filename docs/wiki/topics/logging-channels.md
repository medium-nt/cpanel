# Системы логирования — каналы и аудит

> Last reviewed: 2026-06-28

## Обзор

Система логирования разделена по доменам для лучшей аналитики и audit. Каждый
канал предназначен для специфических типов операций и имеет свой файл хранения с
ротацией.

## Каналы логирования

### Основные каналы

- `marketplace_api` — API взаимодействия с маркетплейсами (Ozon, Wildberries)
- `erp` — бизнес-процессы системы (заказы, перемещения, статусы)
- `tg` — Telegram-интеграции и уведомления
- `max` — MAX-мессенджер интеграции и уведомления (параллельно с TG)
- `orders` — жизненный цикл заказов (создание, изменение, удаление)
- `items` — операции с позициями заказов
- `materials` — движение материалов на складе
- `inventory` — инвентаризация и остатки
- `marketplace_supplies` — поставки и короба FBO/FBS
- `system` — системные события и ошибки
- `salary` — финансовые операции (начисления, выплаты)
- `work_shift` — работа смен и график сотрудников
- `worker_limits` — лимиты сотрудников и квоты
- `queue` — фонды и очереди задач

### Новые каналы (audit)

- `users` — **audit-логирование HIGH-severity операций над пользователями** (
  безопасность)

## Конвенция audit-логирования

Все HIGH-severity мутирующие операции логируются по统一 шаблону:

```php
Log::channel('channel_name')->info('Операция', [
    'actor_id' => auth()->id(),
    'target_id' => $object->id,
    'changes' => array_keys($object->getChanges()),
    'old' => $beforeValues,
    'new' => $afterValues
]);
```

### Что логируется

1. **Кто** — `auth()->id()` (ID сотрудника, выполнившего операцию)
2. **Над чем** — ID изменяемого объекта (`user_id`, `order_id`, `supply_id` и
   т.д.)
3. **Что изменилось** — массив изменённых полей (
   `array_keys($object->getChanges())`)
4. **Было → Стало** — значения до и после изменения для критичных полей

## Покрытие HIGH-severity операций

### Канал `users` (безопасность)

- `UsersController::store()` — создание пользователя
- `UsersController::destroy()` — удаление пользователя
- `UsersController::tariffsUpdate()` — обновление тарифов
- `UserService::saved()` — все обновления пользователя (update + profileUpdate)

### Канал `salary` (финансы)

- `TransactionController::destroy()` — удаление транзакции
- `TransactionController::storePayoutSalary()` — выплата зарплаты
- `TransactionController::storePayoutBonus()` — выплата бонусов

### Канал `marketplace_supplies` (поставки)

- `SupplyBoxController::destroy()` — удаление короба
- `SupplyBoxController::removeOrder()` — удаление заказа из короба
- `SupplyBoxController::closeBox()` — закрытие короба
- `Livewire\BoxOrderScanner::handleScan()` — сканирование заказа
- `Livewire\BoxOrderScanner::removeOrder()` — удаление из сканера
- `Livewire\ShelfChange::saveChanges()` — изменение полок

## Бизнес-правила

- Audit-лог пишется только для HIGH-severity операций, влияющих на безопасность
  или финансы
- Логи сохраняются 30 дней с ежедневной ротацией
- Все операции с пользователями (кроме чтения) должны логироваться
- Финансовые операции обязательны для аудита
- Каналы не смешиваются — каждая операция попадает в свой домен

## Ключевые файлы

- `config/logging.php` — конфигурация всех каналов
- `app/Services/UserService.php` — audit для пользователей
- `app/Http/Controllers/UsersController.php` — контроллер с аудит-логом
- `app/Http/Controllers/TransactionController.php` — финансовые операции
- `app/Http/Controllers/SupplyBoxController.php` — поставки и короба
- `app/Livewire/BoxOrderScanner.php` — сканирование заказов
- `app/Livewire/ShelfChange.php` — работа с полками

## Связанные topics

- [user-management.md](user-management.md) — управление пользователями и ролями
- [salary-system.md](salary-system.md) — система начислений и выплат
- [finance.md](finance.md) — финансовые операции и транзакции
- [marketplace-integration.md](marketplace-integration.md) — интеграция с
  маркетплейсами
- [warehouse-operations.md](warehouse-operations.md) — операции со складом и
  материалами
