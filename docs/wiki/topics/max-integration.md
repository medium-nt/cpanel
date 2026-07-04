# MAX Integration — Интеграция с мессенджером MAX

> Last reviewed: 2026-07-04

## Обзор

MAX — второй (наряду с Telegram) канал доставки уведомлений системы сотрудникам.
Бот получает входящие сообщения через webhook (`POST /api/max/webhook`), по
которому происходит привязка пользователей и исполнение текстовых команд
(`например, /users`). Исходящие уведомления отправляются через `MaxService` с
защитой Circuit Breaker (cache-флаги на 429/403).

## Как это работает

### Общая схема

```
MAX платформа ──webhook──▶ MaxController::webhook() ──▶ обработка
                                   │
                                   ├─ MaxService::sendMessage() ──▶ MAX Bot API
                                   └─ Log::channel('max')
```

Два режима приёма сообщений взаимоисключающи:

- **Webhook** (`POST /api/max/webhook`) — основной режим в production: MAX
  присылает одиночный `Update` на каждое событие `message_created`.
- **Long-polling** (`GET /updates`) — только для dev: ответ приходит обёрнутым в
  `{updates: [...]}`. Webhook и `/updates` нельзя включать одновременно.

`MaxController::webhook()` одинаково обрабатывает оба формата: если в payload нет
ключа `updates`, но есть `message`, контроллер оборачивает payload в массив из
одного элемента — это и есть одиночный Update от webhook.

### Маршрут webhook

```php
// routes/api.php (БЕЗ middleware — публичный endpoint)
Route::post('/max/webhook', [MaxController::class, 'webhook']);
```

Маршрут НЕ защищён `auth` или `require_open_shift` — MAX вызывает его снаружи.
Ответ всегда `['status' => 'ok']` (HTTP 200), чтобы платформа не повторяла
доставку.

### Логика webhook

**1. Разбор payload.** Поддерживаются два формата:

| Источник             | Формат payload                         | Нормализация                              |
|----------------------|----------------------------------------|-------------------------------------------|
| Webhook              | одиночный `Update` (есть `message`)    | оборачивается в `[$payload]`              |
| Long-polling /updates| `{updates: [...]}`                     | берётся как есть                          |

Каждый входящий payload целиком логируется в канал `max` через
`Log::channel('max')->info(json_encode(...))`.

**2. Извлечение данных сообщения.** Для каждого Update:

- `chat_id` = `$update['message']['recipient']['chat_id']` (приводится к строке);
  если отсутствует — Update пропускается.
- `text` = `$update['message']['body']['text']` (trim'ится, default `''`).

**3. Определение типа пользователя.** Идёт поиск `User` по `where('max_id',
$chatId)->first()`:

- **Незарегистрированный** (нет записи с таким `max_id`) → ВСЕГДА получает ссылку
  на авторизацию, даже если написал команду:
  ```
  Привет! Я бот компании Мегатюль. Для начала работы вы должны авторизоваться по этой ссылке:
  <route('profile', ['max_id' => $chatId])>
  ```
- **Зарегистрированный** → текст разбирается как команда (см. ниже), любая
  нераспознанная команда → приветствие:
  ```
  Привет, <name>! Вы уже авторизованы в системе как <роль> и теперь будете
  получать все уведомления системы через меня.
  ```
  Роль подставляется через `UserService::translateRoleName()`.

### Команды бота

Текст зарегистрированного пользователя разбирается на команды. Сейчас
поддерживается одна:

| Команда  | Доступ          | Описание                                                | Формат ответа                                                                                          |
|----------|-----------------|---------------------------------------------------------|--------------------------------------------------------------------------------------------------------|
| `/users` | только `admin`  | Список всех пользователей, подключённых к MAX           | Заголовок «Подключённые пользователи:» + построчно «Фамилия И.О. — Роль». При пустом списке: «Нет подключённых пользователей.» |

**Доступ и отказ:**

- **admin** — получает список.
- **любая другая роль** — молчаливый отказ: `handleUsersCommand()` просто
  возвращается, ответ НЕ отправляется.
- **незарегистрированный** — см. выше, всегда получает ссылку на авторизацию
  (команда не разбирается).

**Содержимое ответа `/users`:**

```php
UserService::getConnectedToMaxUsers()  // whereNotNull('max_id')->where('max_id', '!=', '')->orderBy('name')
    ->map(fn (User $u) => sprintf('%s — %s',
        $u->short_name,                                  // accessor getShortNameAttribute → «Фамилия И.О.»
        UserService::translateRoleName($u->role->name)   // человекочитаемая роль
    ));
```

Склейка строк — через `\n`, перед списком заголовок
`"Подключённые пользователи:\n"`. Если ни у кого не заполнен `max_id` — ответ
одной строкой «Нет подключённых пользователей.»

### Роли и доступ

Доступ к командам бота определяется через `User->role->name`:

- `admin` — единственная роль с доступом к `/users`. Проверка:
  `if ($user->role->name !== 'admin') { return; }`.
- Остальные роли (`seamstress`, `cutter`, `otk`, `storekeeper`, `manager`,
  `driver`, `cleaner`) — НЕ получают ответа на `/users` (молчаливый skip).
- Незарегистрированные пользователи — всегда получают ссылку на привязку
  профиля (независимо от текста).

### MaxService и Circuit Breaker

`MaxService::sendMessage(?string $chatId, string $text): bool` — статический
метод отправки сообщений. Возвращает `true` при успехе, `false` при пропуске или
ошибке (НЕ бросает исключения, чтобы не ронять ~32 синхронные точки вызова).

**Circuit Breaker по cache-флагам:**

| HTTP-ответ / условие                       | Cache-ключ                         | TTL     | Поведение                              |
|--------------------------------------------|------------------------------------|---------|----------------------------------------|
| `429` или code `too.many.requests`         | `max:rate_limited:{chatId}`        | 30 мин  | тихий skip последующих отправок         |
| `403` с `message` содержит `dialog.suspended` | `max:banned:{chatId}`           | 6 ч     | тихий skip последующих отправок         |
| любой другой error                         | —                                  | —       | лог ошибки в канал `max`, return false |

При наличии любого флага последующие вызовы skip'ятся ещё до HTTP-запроса
(читаются через `Cache::has()`). Бизнес-смысл: защита от retry-шторма (1
доставленное сообщение может породить ~20 пустых запросов) и эскалации
429→403=бан бота.

**Технические нюансы MAX API:**

- Authorization: RAW-токен в заголовке БЕЗ префикса `Bearer ` (иначе 401).
- API-домен: `platform-api2.max.ru` (`platform-api.max.ru` deprecated, дедлайн
  миграции 19.07.2026).
- HTTP-клиент: `Http::withHeaders(['Authorization' => token])
  ->withOptions(['verify' => config('services.max.verify_ssl')])->timeout(10)`.
- В **non-production** окружении HTTP НЕ выполняется — метод только логирует
  сообщение в канал `max` и возвращает `true`. Это позволяет тестировать webhook
  и рассылки через `Log::shouldReceive('channel')`.

### Логирование

Все операции MAX пишутся в отдельный канал `max` (`storage/logs/max/`):

- `MaxController::webhook()` — входящий payload целиком (info).
- `MaxService::sendMessage()` — debug-отправка в non-prod (info), ошибки (error),
  события Circuit Breaker 429/403 (warning).

### Тестирование

В testing-окружении `MaxService::sendMessage()` НЕ делает HTTP-запрос, а только
пишет в лог. Поэтому проверки строятся на шпионаже за логом:

```php
Log::shouldReceive('channel')->with('max')->andReturnSelf();
Log::shouldReceive('info')->with(Mockery::on(fn ($s) => str_contains($s, '...')));
```

Это позволяет тестировать:

- отправку ссылки на авторизацию незарегистрированному пользователю;
- формирование списка `/users` (формат строк «Фамилия И.О. — Роль»);
- молчаливый отказ не-admin на `/users`;
- приветствие зарегистрированному пользователю при неизвестном тексте.

## Ключевые файлы

- `app/Http/Controllers/MaxController.php` — webhook (webhook + handleUsersCommand
  приватный). Разбирает payload, определяет тип пользователя, делегирует команды.
- `app/Services/MaxService.php` — `sendMessage()` с Circuit Breaker (429/403),
  `handleFailedResponse()`. Non-prod → только лог.
- `app/Services/UserService.php` — `getConnectedToMaxUsers()` (выборка по
  `max_id`), `translateRoleName()` (русские названия ролей).
- `app/Services/NotificationService.php` — единый шлюз уведомлений, использует
  MaxService параллельно с TgService.
- `app/Jobs/SendMaxMessageJob.php` — очередь MAX-уведомлений (клон
  SendTelegramMessageJob).
- `app/Console/Commands/SubscribeMaxWebhook.php` — регистрация webhook в MAX API
  (команда `max:subscribe-webhook`).
- `app/Models/User.php` — поле `max_id`, accessor `short_name`
  (`getShortNameAttribute` → «Фамилия И.О.»).
- `routes/api.php` — `POST /api/max/webhook` (без middleware).
- `config/services.php` — секция `max`: `token`, `api_url`,
  `verify_ssl` (для shared-хостингов без актуального CA-бандла).
- `config/logging.php` — канал `max` (writer → `storage/logs/max/`).

## Бизнес-правила

- **Привязка MAX:** `users.max_id` заполняется через
  `GET /megatulle/users/profile?max_id=...` (webhook отправляет незарегистрированному
  ссылку на этот роут). Отключение — роут `profile.disconnectMax`.
- **Webhook без middleware:** `POST /api/max/webhook` публичный; всегда отвечает
  `{status: ok}`, чтобы MAX не повторял доставку.
- **Незарегистрированный пользователь ВСЕГДА получает ссылку на авторизацию** —
  даже если он написал команду (`/users`). Разбор команд включается только для
  зарегистрированных пользователей.
- **Доступ к `/users` — только `admin`.** Не-admin получает молчаливый отказ (НЕ
  ответ вообще). Проверка на уровне `role->name`, а не Gate/Policy.
- **Источник списка `/users`** — только пользователи с непустым `max_id`
  (`whereNotNull('max_id')->where('max_id', '!=', '')`), сортировка по `name`.
- **Формат ФИО** — accessor `User::short_name` (Фамилия + инициалы через пробел),
  роль — `UserService::translateRoleName()`.
- **Circuit Breaker:** 429 → 30 мин блок, 403 `dialog.suspended` → 6 ч блок.
  Сервис остаётся «тихим» (не бросает исключения).
- **Очереди:** массовые рассылки идут через `SendMaxMessageJob` (NOTICE
  «отправлено» только при `true`, warning при `false`/CB; retry при 429/403 НЕ
  делается).
- **Webhook и long-polling `/updates` взаимоисключающи** — long-polling только
  для dev.
- **Authorization MAX** — RAW-токен без `Bearer ` префикса.

## Связанные topics

- [user-management.md](user-management.md) — `users.max_id`, привязка/отключение
  мессенджера, роли (admin-only для `/users`), NotificationService.
- [logging-channels.md](logging-channels.md) — канал `max` для вебхука и
  Circuit Breaker событий.
- [marketplace-integration.md](marketplace-integration.md) — TG+MAX дублирование
  уведомлений в цепочках обработки заказов.
