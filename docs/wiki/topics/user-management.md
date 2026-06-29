# User Management — Управление пользователями

> Last reviewed: 2026-06-29

## Обзор

Система управления пользователями включает роли, смены, цехи и фильтрацию на
странице списка сотрудников. Пользователи организованы по ролям и сменам, с
поддержкой multi-workshop архитектуры.

## Как это работает

### Ролевая система

Система использует кастомные роли (не spatie):

- `admin` — администраторы
- `storekeeper` — кладовщики
- `seamstress` — швеи
- `cutter` — закройщики
- `otk` — ОТК (отдел технического контроля)
- `driver` — водители
- `manager` — менеджеры
- `cleaner` — уборщицы (добавлена 17.06.2026)

**Специализация ролей:**

- `ShiftService::SHIFT_ROLES` = ['seamstress', 'cutter', 'otk] — только эти роли
  привязаны к сменному графику
- Админы, менеджеры и кладовщики работают независимо от смен
- `cleaner` (уборщица) и `driver` (водитель) — минимальный доступ, не участвуют
  в сменах, не видят модули склада/маркетплейса/производства, но имеют доступ в
  киоск любого цеха только для учёта рабочего времени → для них это критично для
  начисления оклада

### Фильтрация на странице списка пользователей

На странице `/megatulle/users` (роут `users.index`) реализована фильтрация:

**1. Фильтр по роли** — по полю `users.role_id`

- Выпадающий список с ролями
- Авто-применение через `PageQueryParam.js` (onchange)

**2. Фильтр по текущему цеху** — по последней смене сотрудника

- У User НЕТ прямого поля `workshop_id`
- Текущий цех определяется через смены: `workshop_id` последней записи в
  `shift_user` с `effective_from <= сегодня`
- Реализовано коррелированным подзапросом в `UserService::getFiltered()`
- Авто-применение через `PageQueryParam.js`

**Колонка «Цех»** в таблице списка отображает `$user->currentWorkshop()?->title`

### Определение текущего цеха

`User::currentWorkshop()` — метод возвращает текущий цех пользователя:

```php
// Псевдокод: workshop_id последней смены с effective_from <= сегодня
return $this->shifts()
    ->where('effective_from', '<=', now())
    ->latest()
    ->first()
    ?->workshop;
```

**Бизнес-правило:** сотрудник может принадлежать только одному цеху в данный
момент.

### Фильтрация в UserService

`UserService::getFiltered(Request): Builder` — статический метод для фильтрации
пользователей:

```php
// Фильтр по роли
if ($request->role_id) {
    $query->where('role_id', $request->role_id);
}

// Фильтр по текущему цеху (через смены)
if ($request->workshop_id) {
    $query->whereRaw('(SELECT uw.workshop_id FROM shift_user su 
        JOIN shift_schedule ss ON su.shift_id = ss.shift_id 
        WHERE su.user_id = users.id AND ss.effective_from <= CURDATE() 
        ORDER BY su.effective_from DESC LIMIT 1) = ?', 
        [$request->workshop_id]);
}
```

### Методы User

**Связи с сменами:**

- `shifts()` — все смены пользователя (через pivot `shift_user`)
- `currentShift()` — текущая смена (effective_from <= сегодня)
- `currentWorkshop()` — текущий цех через смену

**Проверки доступа:**

- `canWorkToday()` — может ли сегодня работать (см. shift-system.md для деталей)
- `hasShift()` — привязан ли к смене

### Мессенджер-интеграции

Система поддерживает параллельную работу с двумя мессенджерами для уведомлений:

**Telegram (TG):**

- Колонка `users.tg_id` (varchar 255, nullable) — хранит Telegram chat_id
- Привязка через GET `/megatulle/users/profile?tg_id=...` (webhook от TG-бота)
- Отключение через роут `profile.disconnectTg`
- Отправка через `TgService::sendMessage(?string $chatId, string text)`

**MAX (новый, с 28.06.2026):**

- Колонка `users.max_id` (varchar 255, nullable) — хранит MAX chat_id (аналог
  tg_id)
- Привязка через GET `/megatulle/users/profile?max_id=...` (webhook от MAX-бота)
- Отключение через роут `profile.disconnectMax`
- Отправка через `MaxService::sendMessage(?string $chatId, string text)`
- Webhook: POST `/api/max/webhook` (разбирает payload MAX, событие
  `message_created`,
  chat_id в `message.recipient.chat_id`)
- Подписка webhook: команда `max:subscribe-webhook` (регистрирует в MAX API)

**Единый шлюз уведомлений (NotificationService, Этап 2 из 2 — реализован):**

`NotificationService::notify(User $user, string $text, bool $queued = false, ?int $delaySeconds = null): void`
— единственная точка входа для отправки уведомлений в оба канала:

- Если `$queued = false` (default) → синхронно: TgService/MaxService
- Если `$queued = true` → через очереди:
  SendTelegramMessageJob/SendMaxMessageJob
  (с опциональной задержкой `$delaySeconds`)
- Если у пользователя есть tg_id → отправка в Telegram
- Если у пользователя есть max_id → отправка в MAX
- Если у пользователя нет обоих каналов → тихий пропуск (без ошибки)
- Паттерн DRY: логика каналов в одном месте, вместо дублирования `foreach` по
  `tg_id`
    + вызовов `TgService`/`SendTelegramMessageJob`

**Стратегия рассылки (Этап 2 из 2 — реализован):**

Все массовые рассылки (поступление материалов, автозаказы, дефекты,
сканирование)
переведены на NotificationService. Вместо `foreach ($tgIds as $tgId) {
TgService/SendTelegramMessageJob }` теперь `foreach ($users as $user) {
NotificationService::notify($user, $text[, queued: true, delaySeconds: $i]) }`.

**Технические нюансы MAX:**

- Authorization: RAW токен в заголовке БЕЗ префикса 'Bearer ' (иначе 401)
- API домен: `platform-api2.max.ru` (см. platform-api.max.ru deprecated, дедлайн
  19.07.2026)
- Long polling /updates и webhook взаимоисключающи; long-polling — только для
  dev

## Ключевые файлы

- `app/Models/User.php` — модель пользователя (роли, связи с сменами,
  tg_id/max_id)
- `app/Services/UserService.php` — фильтрация пользователей (`getFiltered()`
  метод),
  хелперы массовой рассылки (`getListSeamstressesWorkingToday()`,
  `getListStorekeepersWorkingToday()`, `getListManagersWithTg()` — возвращают
  `Collection<User>`, фильтр по `tg_id OR max_id`)
- `app/Services/NotificationService.php` — единый шлюз уведомлений (notify
  метод)
- `app/Services/TgService.php` — static sendMessage для Telegram-уведомлений
- `app/Services/MaxService.php` — static sendMessage для MAX-уведомлений (клон
  TgService)
- `app/Jobs/SendTelegramMessageJob.php` — очередь TG-уведомлений
- `app/Jobs/SendMaxMessageJob.php` — очередь MAX-уведомлений
- `app/Http/Controllers/UsersController.php` — страница списка (`index()`
  метод),
  методы `profile()`, `disconnectTg()`, `disconnectMax()`
- `app/Http/Controllers/MaxController.php` — webhook MAX (POST /api/max/webhook)
- `app/Console/Commands/SubscribeMaxWebhook.php` — регистрация webhook в MAX
- `resources/views/users/index.blade.php` — UI с фильтрами и колонкой «Цех»
- `resources/views/users/profile.blade.php` — блок статуса MAX/TG подключения
- `config/services.php` — секции `telegram` и `max` (token, api_url, admin_id,
  webhook_url)

## Связанные topics

- [shift-system.md](shift-system.md) — смены и цехи, графики работы
- [salary-system.md](salary-system.md) — оплата по ролям и сменам
- [order-lifecycle.md](order-lifecycle.md) — доступ к заказам по ролям

## Бизнес-правила

- Пользователи могут менять роли только через админ-интерфейс
- Смена цеха происходит через перевод в новую смену с указанием даты
- Только админы видят всех пользователей (фильтрация по ролям/цехам)
- Фильтр по цеху всегда актуален — обновляется при изменении смены сотрудника
- Новые пользователи создаются без привязки к смене (нужно назначать вручную)
- Все операции с пользователями (кроме чтения) логируются для безопасности
- **Мессенджеры-интеграции:** tg_id и max_id независимы, пользователь может
  иметь оба или ни одного
- **NotificationService:** уведомления уходят в оба канала параллельно при
  наличии
  chat_id
  (tg_id → Telegram, max_id → MAX), через TgService/MaxService синхронно или
  очереди
  (SendTelegramMessageJob/SendMaxMessageJob) с задержкой
- **UserService-хелперы рассылки:** `getListSeamstressesWorkingToday()`,
  `getListStorekeepersWorkingToday()`, `getListManagersWithTg()` возвращают
  `Collection<User>`, фильтр —
  `where(fn $q => $q->whereNotNull('tg_id')->orWhereNotNull('max_id'))`
  (сотрудник попадает если привязан ХОТЯ БЫ один мессенджер)
- **Webhook MAX:** при событии `message_created` и несуществующем max_id
  отправляется
  ссылка на профиль для привязки (route('profile', ['max_id' => ...]))

## Связанные topics

- [shift-system.md](shift-system.md) — смены и цехи, графики работы
- [salary-system.md](salary-system.md) — оплата по ролям и сменам
- [order-lifecycle.md](order-lifecycle.md) — доступ к заказам по ролям
- [logging-channels.md](logging-channels.md) — каналы логирования и
  audit-покрытие, канал `max` для MAX-уведомлений
- [warehouse-operations.md](warehouse-operations.md) — доступ к киосу по ролям
