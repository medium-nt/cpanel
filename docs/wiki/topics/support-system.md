# Support System — Тикет-система «Тикеты»

> Last reviewed: 2026-06-30

## Обзор

Тикет-система «Тикеты» (ранее «Поддержка») позволяет сотрудникам сообщать о
проблемах в работе системы. Сотрудники создают тикеты с описанием проблемы,
прикрепляют скриншоты и указывают URL страницы, где возникла проблема.
Администраторы обрабатывают тикеты и закрывают их после решения.

## Как это работает

### Жизненный цикл тикета

```
new ──start──▶ in_progress ──close──▶ closed
 │                 │
 └──────delete───────▶ deleted ◀──delete──┘
```

**Lifecycle НЕ линейный:** deleted — это финальная корзина, в которую можно
попасть из любого статуса. **Закрыть тикет можно ТОЛЬКО из `in_progress`** —
сначала админ переводит тикет в работу (`start`), потом закрывает (`close`).

**Статусы тикета:**

- `new` — новый тикет, требует обработки (показывается в «Новые», бейдж меню)
- `in_progress` — тикет в работе (показывается в «Новые», НЕ в бейдже меню)
- `closed` — закрыт администратором с ОБЯЗАТЕЛЬНЫМ комментарием (показывается в
  «Обработанные»)
- `deleted` — удалён в корзину администратором (показывается в «Обработанные»)

**Методы переходов:**

- `markInProgress()` — перевести в статус `in_progress` (start)
- `markClosed(?string $adminComment = null)` — перевести в статус `closed`,
  проставляет `closed_at = now()` + комментарий (сервис `close()` всегда
  передаёт непустую строку)
- `markDeleted()` — перевести в статус `deleted` (корзина)
- `isClosed()` / `isDeleted()` — проверки статусов

### Создание тикета

**Доступ:** любой авторизованный сотрудник (без требования открытой смены)

**Поля:**

- `description` (text, required) — описание проблемы, max 5000 символов
- `page_url` (string, nullable) — URL страницы, где возникла проблема, max 500
- `screenshot` (file, nullable) — скриншот проблемы, только изображение, max 5MB
- `admin_comment` (text, required при закрытии) — комментарий администратора при
  закрытии («что сделано»), виден автору тикета, max 5000 символов

**Предзаполнение URL:**

Кнопка создания тикета в navbar использует серверный подход:

```blade
<a href="{{ route('tickets.create', ['url' => request()->fullUrl()]) }}" class="btn btn-sm btn-warning" aria-label="Сообщить о проблеме">
    <span class="d-none d-md-inline">Сообщить о проблеме</span>
    <i class="fas fa-bug"></i>
</a>
```

Текущий URL передаётся через query-параметр `url` на серверной стороне (
`request()->fullUrl()`).
На мобильных устройствах (ниже md) показывается только иконка-жук, текст скрыт.

### Просмотр тикетов

**Сотрудники видят:**

- Только свои тикеты (scope `forUser(User)`)
- Вкладка «Новые» (`scope=opened`) — их собственные тикеты со статусами `new` И
  `in_progress` (scope `opened()` = `whereIn('status', [new, in_progress])`)
- Вкладка «Обработанные» (`scope=processed`) — их тикеты со статусами `closed`
  или `deleted`

**Администраторы видят:**

- Все тикеты (без фильтрации `forUser`)
- Вкладка «Новые» — все тикеты со статусами `new` И `in_progress` (бейдж меню
  считает только `new`)
- Вкладка «Обработанные» — все тикеты со статусами `closed` или `deleted`

**Навигация:**

- `GET /megatulle/tickets?scope=opened` — вкладка «Новые» (дефолт, статусы new +
  in_progress)
- `GET /megatulle/tickets?scope=processed` — вкладка «Обработанные» (статусы
  closed + deleted)

**Счётчики в табах:**

- «Новые» — красный бейдж `badge-danger` с количеством
  `Ticket::opened()->count()` (статусы new + in_progress, фильтр по автору для
  сотрудников, все — для админов)
- «Обработанные» — серый бейдж `badge-secondary` с количеством
  `Ticket::processed()->count()` (фильтр по автору для сотрудников, все — для
  админов)
- Бейдж скрыт при `count() === 0`

**ВАЖНО:** in_progress остаётся в табе «Невые» — отдельного таба нет.

### Обработка тикетов (администратор)

**Перевод в работу (start):**

- `PUT /megatulle/tickets/{ticket}/start` (роут `tickets.start`)
- Доступ: только `isAdmin()` И статус `new` (только из new, защита через
  `TicketPolicy`)
- Изменяет статус на `in_progress` (через `TicketService::start()`)
- Логирование:
  `Log::info('Тикет переведён в работу', ['ticket_id' => $ticket->id, 'admin_id' => $admin->id])`
- Бейдж меню УБИРАЕТ тикет из счётчика (т.к. считаются только `new`)

**Закрытие тикета:**

- `PUT /megatulle/tickets/{ticket}/close` (один `tickets`, не двойной!)
- Доступ: только `isAdmin()` И статус `in_progress` (защита — НЕЛЬЗЯ закрыть
  напрямую из `new`)
- Валидация: `admin_comment` required, max 5000 символов
- Изменяет статус на `closed` + проставляет `closed_at = now()` + обязательный
  `admin_comment` (через `TicketService::close(string $adminComment)`)
- Guard в сервисе: возвращает `false` если статус не `in_progress` ИЛИ
  `trim($adminComment) === ''`
- Логирование:
  `Log::info('Тикет закрыт администратором', ['ticket_id' => $ticket->id, 'admin_id' => $admin->id])`

**Удаление в корзину:**

- `PUT /megatulle/tickets/{ticket}/delete` (один `tickets`, не двойной!)
- Доступ: только `isAdmin()` И статус в `[new, in_progress]` (защита от
  повторного удаления через `TicketPolicy`)
- Изменяет статус на `deleted` (НЕ удаляет запись из БД)
- Логирование:
  `Log::info('Тикет перемещён в корзину администратором', ['ticket_id' => $ticket->id, 'admin_id' => $admin->id])`

### Бейдж новых тикетов

**Показ в меню:**

- Пункт меню «Тикеты» (ранее «Поддержка») имеет бейдж с количеством тикетов в
  статусе `new` (НЕ `in_progress` — тикеты в работе уже не требуют внимания)
- Бейдж показывается только администраторам И только когда `count() > 0`
- Количество — `Ticket::query()->where('status', Ticket::STATUS_NEW)->count()` (
  считает только new)
- После перевода тикета в `in_progress` счётчик УМЕНЬШАЕТСЯ (тикет больше не
  new)

**Реализация:**

- `AppServiceProvider::boot()` — слушает событие `BuildingMenu::class`
- Логика: если админ И есть пункт с `key='support'` И `count() > 0`:
    - `$event->menu->remove('support')` — удаляет существующий пункт
    - `$event->menu->addBefore('logs', [...])` — пересоздаёт пункт с
      `'label' => $count`, `'label_color' => 'danger'`, вставляет перед
      пунктом 'logs'
- Пункт «Тикеты» определён статически в `config/adminlte.php` с
  `key => 'support'`, `text => 'support'` → перевод 'Тикеты' из
  `lang/vendor/adminlte/ru/menu.php` (ключ `'support' => 'Тикеты'`)
- **Позиция в меню:** самый низ — после «Настройки» (settings submenu), перед
  «Просмотр логов» (logs). Бейдж не уводит пункт наверх.
- SQL-запрос: `SELECT COUNT(*) FROM tickets WHERE status = 'new'`

### Авторизация (TicketPolicy)

| Действие | Кто можете                                   | Правило                                                                                               |
|----------|----------------------------------------------|-------------------------------------------------------------------------------------------------------|
| `view`   | Автор тикета ИЛИ админ                       | `$user->id === $ticket->user_id \|\| $user->isAdmin()`                                                |
| `create` | Любой сотрудник                              | `true` (без требований смены)                                                                         |
| `start`  | Только админ И статус `new`                  | `$user->isAdmin() && $ticket->status === Ticket::STATUS_NEW` (только из new)                          |
| `close`  | Только админ И статус `in_progress`          | `$user->isAdmin() && $ticket->status === Ticket::STATUS_IN_PROGRESS` (НЕЛЬЗЯ закрыть напрямую из new) |
| `delete` | Только админ И статус в `[new, in_progress]` | `$user->isAdmin() && in_array($ticket->status, [Ticket::STATUS_NEW, Ticket::STATUS_IN_PROGRESS])`     |
| `index`  | Сотрудник видит свои, админ — все            | scope `forUser` для не-админов                                                                        |

**Defence in depth:**

- `TicketService::start()` возвращает `false` если статус не `new` (дублирующая
  проверка на уровне сервиса)
- `TicketService::close()` возвращает `false` если статус не `in_progress` ИЛИ
  `trim($adminComment) === ''` (guard на обязательный комментарий)
- `TicketService::delete()` возвращает `false` если статус не в
  `[new, in_progress]` (дублирующая проверка на уровне сервиса)

**ВАЖНО:** Роуты тикетов находятся под `auth` middleware, но БЕЗ
`require_open_shift` — создание тикетов доступно даже без открытой смены.

## Ключевые файлы

- `app/Models/Ticket.php` — модель тикета, константы
  `STATUS_NEW/IN_PROGRESS/CLOSED/DELETED`, scopes `opened()` (whereIn new +
  in_progress),
  `processed()`, `forUser(User)`, методы `markInProgress()`,
  `markClosed(?string)`, `markDeleted()`,
  `isNew()`, `isInProgress()`, `isClosed()`, `isDeleted()`, relation `user()` →
  `belongsTo(User)->withTrashed()`, fillable `admin_comment`, BADGE_COLORS (
  in_progress = warning)
- `app/Http/Controllers/TicketController.php` — CRUD операции, вкладки
  «Новые»/«Обработанные» (query-параметр `?scope=`), счётчики в табах, eager
  load `->with('user')` (устранение N+1 в таблице админа), методы `start()`,
  `close()` с валидацией admin_comment (required, max 5000)
- `app/Policies/TicketPolicy.php` — правила авторизации (viewAny/create: true,
  view: автор ИЛИ isAdmin, start: isAdmin + статус new, close: isAdmin +
  статус [new, in_progress], delete: isAdmin + статус [new, in_progress])
- `app/Services/TicketService.php` — бизнес-логика: `start()` (с guard и
  логированием), `close(string $adminComment)` (с guard на статус in_progress +
  trim комментария, логированием и сохранением), `delete()` (с guard и
  логированием), возвращает `false` при неверном статусе или пустом
  комментарии (defence in depth)
- `app/Http/Requests/StoreTicketRequest.php` — валидация создания (description
  required/max:5000, page_url nullable/url/max:500, screenshot
  nullable/image/max:5120)
- `database/factories/TicketFactory.php` — фабрика для тестов, state
  `inProgress()` для статуса in_progress
-
`database/migrations/2026_06_30_053314_add_admin_comment_to_tickets_table.php` —
миграция добавления поля admin_comment
- `routes/tickets.php` — роуты (префикс megatulle, middleware auth БЕЗ
  require_open_shift), новый роут `tickets.start` для перевода в работу
- `resources/views/tickets/index.blade.php` — список с вкладками и счётчиками,
  заголовок страницы `'Тикеты'`
- `resources/views/tickets/create.blade.php` — форма создания
- `resources/views/tickets/show.blade.php` — детальная страница тикета с
  проверкой `Storage::disk('public')->exists($ticket->screenshot)` (показ «Файл
  скриншота недоступен» если файл удалён), блок «Комментарий администратора» (
  виден если admin_comment есть),
  кнопки действий по статусам (start из new, close + admin_comment textarea
  required из in_progress, delete из не-deleted)
- `resources/views/layouts/app.blade.php` — кнопка создания тикета с
  предзаполнением URL и `aria-label="Сообщить о проблеме"` (в секции
  `@section('content_top_nav_right')`)
- `config/adminlte.php` — пункт меню «Тикеты» (key='support', text='support',
  позиция перед 'logs')
- `lang/vendor/adminlte/ru/menu.php` — перевод 'support' => 'Тикеты' для пункта
  меню
- `app/Providers/AppServiceProvider.php` — BuildingMenu listener для бейджа
  новых тикетов (remove + addBefore('logs') логика)

## Бизнес-правила

- Создание тикетов доступно всем сотрудникам без требований открытой смены (
  middleware `auth` БЕЗ `require_open_shift`)
- Тикет привязан к автору (`user_id`) через `belongsTo(User)->withTrashed()` (
  автор мягко-удаляемый, тикет остаётся)
- Администратор видит все тикеты, сотрудник — только свои (scope `forUser(User)`
  для не-админов)
- Scope `opened()` (НЕ `new`!) используется для фильтрации статусов
  `[new, in_progress]` (слово
  `new` зарезервировано в PHP)
- Query-параметр `?scope=opened` вызывает scope `opened()` (статусы new +
  in_progress),
  `?scope=processed` — scope `processed()` (closed + deleted)
- **Правило вкладки «Новые»:** в неё попадают тикеты со статусами `new` И
  `in_progress` — отдельного таба для in_progress нет
- **Правило бейджа меню:** считается только статус `new`, тикеты в `in_progress`
  не требуют внимания
- Статус `deleted` — это финальная корзина, в которую можно попасть из `new` ИЛИ
  `in_progress` ИЛИ `closed` (НЕ линейный lifecycle)
- Поле `closed_at` заполняется только при переводе в статус `closed` через
  `markClosed()`
- **Правило закрытия:** закрыть тикет можно ТОЛЬКО из статуса `in_progress` (
  сначала `start`, потом `close`). Закрыть напрямую из `new` — нельзя (403).
- Поле `admin_comment` заполняется ОБЯЗАТЕЛЬНО при закрытии тикета (required,
  trim не пустой, max 5000), виден и автору, и админу
- Сотрудник не может редактировать или удалять свои тикеты (только создавать и
  просматривать)
- **Защита от повторных операций:** `TicketPolicy` + `TicketService` —
  стартовать можно только из `new`,
  закрывать можно только из `in_progress` (НЕ из new!), удалять можно только из
  `[new, in_progress]`
- **Логирование аудита:** операции start/close/delete пишутся в канал `system`
  через
  `Log::info` с `ticket_id` и `admin_id`
- **N+1 устранён:** `TicketController@index` использует
  `Ticket::query()->with('user')` — eager load автора для колонки «Автор» в
  таблице админа
- **Скриншот:** при удалении файла с диска на странице детализации показывается
  «Файл скриншота недоступен» вместо broken image
- **Upload скриншота:** индикатор «Загрузка...» при выборе файла убран (фриз
  браузера из-за антивируса/ОС, превью появляется через `URL.createObjectURL`
  после системной паузы)

## Связанные topics

- [user-management.md](user-management.md) — роли и доступ (админы vs
  сотрудники), проверка `isAdmin()` в policy
- [logging-channels.md](logging-channels.md) — канал `system` для логирования
  операций close/delete (аудит)
