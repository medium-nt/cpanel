# Notifications — Система уведомлений TG/MAX

> Last reviewed: 2026-07-12

## Обзор

Система уведомлений доставляет сообщения сотрудникам через два мессенджера:
Telegram и MAX. Уведомления делятся на синхронные (немедленная отправка, нужны
для user-interaction) и асинхронные (через Job'ы очереди, применяются для
массовых рассылок и admin-оповещений). Единая точка входа —
`NotificationService`, который дублирует сообщения в оба канала (TG + MAX) для
пользователей с привязанными `tg_id`/`max_id`.

## Как это работает

### Общая схема

```
NotificationService ──▶ TgService::sendMessage() ──▶ TG Bot API
                       └─ SendTelegramMessageJob (queued)

                 ──▶ MaxService::sendMessage() ──▶ MAX Bot API
                    └─ SendMaxMessageJob (queued)
```

Каждый вызов `NotificationService` дублирует сообщение в оба мессенджера:

- Если у пользователя есть `tg_id` — уходит в Telegram
- Если у пользователя есть `max_id` — уходит в MAX
- Если есть оба — уходит в оба канала
- Если нет ни одного — пропускается тихо

### Два режима отправки

**1. Синхронный (queued=false):**

- Немедленный вызов `TgService::sendMessage()` / `MaxService::sendMessage()`
- Используется в точках user-interaction: привязка аккаунта (`UsersController`),
  ответы на команды ботов (`TelegramController`, `MaxController`)
- Пример: `NotificationService::notify($user, $text, queued: false)`

**2. Асинхронный (queued=true, по умолчанию):**

- Отправка Job'а в очередь: `SendTelegramMessageJob` / `SendMaxMessageJob`
- Job использует `->afterCommit()` — отправка откладывается до коммита
  DB-транзакции; при rollback Job отбрасывается
- Используется для массовых рассылок и admin-уведомлений
- Пример: `NotificationService::notifyAdmin($text)` или
  `NotificationService::notify($user, $text, queued: true, delaySeconds: $i)`

### NotificationService

`app/Services/NotificationService.php` — единый шлюз уведомлений (НЕ
статический, инстанс через DI).

**Методы:**

1. **
   `notify(User $user, string $text, bool $queued = true, ?int $delaySeconds = null): void`
   **
    - Персональное уведомление сотруднику
    - `$queued = true` → через Job'ы (по умололчанию)
    - `$delaySeconds` → stagger-задержка против 429 при массовых рассылках
    - Примеры:
        - `notify($user, $text, queued: true, delaySeconds: 1)` — Job через 1
          сек
        - `notify($user, $text, queued: false)` — синхронно

2. **
   `notifyAdmin(string $text, bool $queued = true, ?int $delaySeconds = null): void`
   **
    - Admin-уведомление в оба канала (TG + MAX)
    - Получатель: `config('telegram.admin_id')` /
      `config('services.max.admin_id')`
    - По умолчанию queued=true (асинхронно)
    - Заменил 14 точек прямого вызова
      `TgService::sendMessage(config('telegram.admin_id'), $text)`

### Circuit Breaker (защита от 429/403)

Circuit Breaker живёт внутри `TgService`/`MaxService` и применяется одинаково к
синхронным и асинхронным вызовам:

| HTTP-ответ  | Cache-ключ                 | TTL   | Поведение                            |
|-------------|----------------------------|-------|--------------------------------------|
| `429`       | `tg:rate_limited:{chatId}` | 30мин | тихий skip последующих отправок      |
| `403`       | `tg:banned:{chatId}`       | 6ч    | тихий skip последующих отправок      |
| любой error | —                          | —     | лог в канал `tg`/`max`, return false |

**Влияние на Job'ы:**

- Job при запуске вызывает `TgService::sendMessage()` /
  `MaxService::sendMessage()`
- Если CB активен (флаг есть) → сервис возвращает `false` → Job логирует warning
  «пропущено (circuit breaker)» и НЕ retry'ится
- Если CB не активен → отправка успешная → Job логирует NOTICE «отправлено»

**Special cooldown:**

- «Нет материала» при `MarketplaceOrderItemService::notifyNoMaterials` — доп.
  флаг `no_material:item:{itemId}` на 30 мин против спама при повторных кликах

### Синхронные точки (НЕ трогали после рефакторинга)

Остались синхронными по решению пользователя — нужны для немедленного ответа:

1. **Webhooks:** `TelegramController`, `MaxController` — `/start`, `/users` (
   ответы на команды ботов)
2. **Привязка аккаунтов:** `UsersController` — `connectTg`, `connectMax` (показ
   QR-кода / auth token)
3. **Dev-метод:** `SettingController::test` — проверка отправки админу

Всё это вызывает `NotificationService::notify($user, $text, queued: false)`
напрямую.

### Асинхронные точки (после рефакторинга)

**1. Admin-уведомления (14 точек → notifyAdmin):**

- `WorkshopRollScan.php` — сканирование рулонов (малый остаток / лимит превышен)
- `DefectMaterialScan.php` — списание брака
- `UserService.php` — admin-точка 302 (нестандартный attendance)
- `MovementMaterialToWorkshopService.php` — 2 точки (приёмка поставки,
  сохранение)
- `DefectMaterialService.php` — сохранение брака
- `MovementDefectMaterialToSupplierService.php` — возврат поставщику
- `MarketplaceOrderItemService.php` — 2 точки (нет материала, отмена заказа)
- `MarketplaceApiService.php` — 2 точки (ошибки синхронизации)
- `AutoOrderService.php` — авто-заказ материала
- `MarketplaceSupplyController.php` — создание поставки
- `MovementMaterialToWorkshopController.php` — финальная приёмка

**2. Массовые рассылки (стagger против 429):**

- 9 точек в `foreach ($users as $user)` — переключены на
  `notify($user, $text, queued: true, delaySeconds: $index+1)` (каждый следующий
  Job на 1 сек позже предыдущего)
- Примеры: `DefectMaterialService`, `MovementMaterialToWorkshopService`,
  `MarketplaceOrderItemService`, `MovementDefectMaterialToSupplierService`

**3. Рассылка смены (UserService::sendMessageForWorkingTodayEmployees):**

- Раньше: прямой вызов `TgService::sendMessage()` / `MaxService::sendMessage()`
  в foreach
- Теперь:
  `foreach ($schedules as $i => $schedule) { NotificationService::notify($schedule->user, $text, queued: true, delaySeconds: $i+1) }`
- Stagger + delay = защита от 429 при массовом开幕 смены

### afterCommit (гарантия доставки при транзакциях)

Все Job'ы (`SendTelegramMessageJob`, `SendMaxMessageJob`) используют
`->afterCommit()`:

```php
// Пример из NotificationService
if ($queued) {
    SendTelegramMessageJob::dispatch($user->tg_id, $text)->afterCommit();
    SendMaxMessageJob::dispatch($user->max_id, $text)->afterCommit();
} else {
    TgService::sendMessage($user->tg_id, $text);
    MaxService::sendMessage($user->max_id, $text);
}
```

**Смысл:**

- Если код, который вызвал `NotificationService::notify()`, внутри
  DB-транзакции → Job добавится в очередь ТОЛЬКО после успешного коммита
- Если транзакция откатится (rollback) → Job отбрасывается, уведомление НЕ
  уходит
- Защищает от отправки уведомлений об операциях, которые не сохранились в БД

**Пример:** `MovementMaterialToWorkshopController::save_receive()` — внутри
DB-транзакции создаётся `MovementMaterial`, вызывается
`NotificationService::notifyAdmin()`. Если приёмка рухнет → Job отбросится,
админ НЕ получит ложное уведомление.

## Ключевые файлы

- `app/Services/NotificationService.php` — единый шлюз уведомлений (notify,
  notifyAdmin), логика queued/sync, afterCommit
- `app/Services/TgService.php` — `sendMessage()` с Circuit Breaker (429/403),
  non-prod → только лог
- `app/Services/MaxService.php` — аналог TgService для MAX
- `app/Jobs/SendTelegramMessageJob.php` — очередь TG-уведомлений (afterCommit,
  логирование)
- `app/Jobs/SendMaxMessageJob.php` — очередь MAX-уведомлений
- `app/Services/UserService.php` — `sendMessageForWorkingTodayEmployees()` (
  рассылка смены)
- `app/Services/MarketplaceOrderItemService.php` — `notifyNoMaterials()` (
  cooldown 30 мин)
- `app/Livewire/WorkshopRollScan.php` — admin-уведомления при сканировании
  рулонов
- `app/Livewire/DefectMaterialScan.php` — admin-уведомления при списании брака
- `app/Http/Controllers/UsersController.php` — привязка TG/MAX (синхронные
  точки)
- `app/Http/Controllers/TelegramController.php` — webhook TG, команда `/start`
- `app/Http/Controllers/MaxController.php` — webhook MAX, команда `/users`
- `config/services.php` — секции `telegram` и `max` (admin_id, tokens,
  webhook_url)
- `config/logging.php` — каналы `tg` и `max` для логов

## Бизнес-правила

- **Дублирование в оба канала:** каждое уведомление уходит и в TG, и в MAX (у
  кого привязан chat_id). NotificationService проверяет наличие `tg_id`/`max_id`
  у User.
- **Admin-уведомления — через Job:** все 14 точек заменены на
  `NotificationService::notifyAdmin($text)` (queued=true по умолчанию).
- **Массовые рассылки — со stagger:** foreach с `delaySeconds: $index+1` (каждый
  следующий Job на 1 сек позже) против 429.
- **Синхронные точки — только user-interaction:** webhooks (ответы на команды),
  привязка аккаунтов, dev-методы.
- **Circuit Breaker — внутри сервисов:** 429 → 30 мин, 403 → 6 ч. Job'ы при CB
  логируют warning и НЕ retry'ятся.
- **afterCommit — защита от ложных уведомлений:** Job'ы отправляются только
  после успешного коммита DB-транзакции.
- **Cooldown «нет материала» — 30 мин:** флаг `no_material:item:{itemId}`
  защищает от спама при повторных кликах.
- **Non-prod окружение — только лог:** `TgService::sendMessage()` и
  `MaxService::sendMessage()` в testing/local NOT делают HTTP, только пишут в
  лог.

## Связанные topics

- [max-integration.md](max-integration.md) — MAX webhook, команды бота,
  MaxService детали
- [user-management.md](user-management.md) — `users.tg_id`/`max_id`, привязка
  мессенджеров
- [shift-system.md](shift-system.md) — рассылка о открытии смены через
  UserService
- [marketplace-integration.md](marketplace-integration.md) — admin-уведомления
  при синхронизации заказов/поставок
- [material-flow.md](material-flow.md) — admin-уведомления при движении
  материалов (приёмка, брак, возврат)
- [order-lifecycle.md](order-lifecycle.md) — уведомления «нет материала» при
  выдаче заказов
