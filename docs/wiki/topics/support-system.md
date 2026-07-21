# Support System — Тикет-система «Тикеты»

> Last reviewed: 2026-07-21

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
- `markClosed(int $adminId, string $adminComment)` — перевести в статус
  `closed`,
  проставляет `closed_at = now()`, `admin_id` (кто ответил) + комментарий
- `markDeleted()` — перевести в статус `deleted` (корзина)
- `markAnswerRead()` — пометить ответ как прочитанный автором (проставляет
  `answer_read_at = now()`)
- `isClosed()` / `isDeleted()` / `isAnswerUnread()` — проверки статусов

**Новые поля (2026-07-21):**

- `admin_id` (FK→users, nullable) — зафиксировано какой админ ответил на тикет
- `answer_read_at` (timestamp, nullable) — когда автор прочитал ответ админа (
  null
  = непрочитано)

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

### Карточка тикета (2026-07-21)

**Отображение информации об авторе:**

- ФИО автора (`$ticket->user->name`) — ссылка на профиль редактирования (
  `route('users.edit', $ticket->user)`)
- Рядом с ФИО — бейдж роли на русском языке через
  `UserService::translateRoleName($ticket->user->role->name)`
- Пример: «Иванов Иван Иванович [Швея]» — ФИО кликабельно, бейдж серый
  `badge-secondary`

**Отображение ответа админа:**

- Под блоком `admin_comment` (ответ админа) — подпись с ФИО, ролью и датой:
  `Ответил: Петров Петр Петрович (Администратор), 21.07.2026 15:30`
- ФИО админа: `$ticket->admin->name` (relation `admin()` через `belongsTo(User)
 `)
- Роль админа: `UserService::translateRoleName($ticket->admin->role->name)`
- Дата закрытия: `$ticket->closed_at?->format('d.m.Y H:i')`
- Eager load: `TicketController@show()` загружает `->with('user.role',
  'admin.role')` (устранение N+1)

**Пометка ответа прочитанным:**

- При просмотре тикета автором (НЕ админом) вызывается
  `$ticket->markAnswerRead()`
- Проставляется `answer_read_at = now()` — ответ больше не учитывается в бейдже
  непрочитанных
- Для админов ответ НЕ помечается прочитанным (админ не автор)

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

### Уведомление автора об ответе (2026-07-21)

**Автоматическая отправка при закрытии:**

- После закрытия тикета автор автоматически получает уведомление в Telegram ИЛИ
  MAX (через `NotificationService::notify`, queued)
- Условие отправки: у юзера должен быть `tg_id` ИЛИ `max_id` (привязан хотя бы
  один мессенджер)
- Юзеры без `tg_id`/`max_id` пропускаются тихо (без ошибок)

**Текст уведомления:**

```
Поступил ответ по обращению в поддержку

Ваш вопрос:
{текст вопроса автора, обрезка до 300 символов}

Ответ администратора ({ФИО админа}):
{admin_comment}

Ссылка на тикет: {URL на страницу детализации}
```

**Реализация:**

- `TicketService::notifyAuthorOfAnswer(Ticket $ticket)` — приватный метод,
  вызывается в `close()` после сохранения тикета
- Использует `NotificationService::notify($ticket->user, $text, queued: true)`
- ФИО админа: `$admin->name` (полное имя через `UserService::getFullName()`)
- Текст вопроса обрезается через `Str::limit($ticket->description, 300)``

### Бейджи в меню (две логики)

**1. Бейдж новых тикетов (администраторы):**

- Пункт меню «Тикеты» (ранее «Поддержка») имеет бейдж с количеством тикетов в
  статусе `new` (НЕ `in_progress` — тикеты в работе уже не требуют внимания)
- Бейдж показывается только администраторам И только когда `count() > 0`
- Цвет: красный `danger` — новые тикеты требуют внимания админа
- Количество — `Ticket::query()->where('status', Ticket::STATUS_NEW)->count()` (
  считает только new)
- После перевода тикета в `in_progress` счётчик УМЕНЬШАЕТСЯ (тикет больше не
  new)

**Реализация бейджа новых:**

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

**2. Бейдж непрочитанных ответов (сотрудники, 2026-07-21):**

- Зелёный бейдж `success` для НЕ-админов — показывает сколько у сотрудника
  тикетов с непрочитанным ответом админа
- Условие: `status = closed` И `admin_id IS NOT NULL` И `answer_read_at IS NULL`
- Количество: `Ticket::forUser($user)->unreadAnswers()->count()`
- Цвет: зелёный `success` — информационный бейдж (не требует срочного внимания)
- У администраторов этот бейдж НЕ показывается (остаётся только красный бейдж
  новых тикетов)

**Реализация бейджа непрочитанных:**

- `Ticket::scopeUnreadAnswers()` — запрос: `where('status', 'closed')
  ->whereNotNull('admin_id')->whereNull('answer_read_at')`
- `TicketController::show()` — при просмотре тикета автором вызывает
  `$ticket->markAnswerRead()` (проставляет `answer_read_at = now()`)
- После просмотра тикета бейдж УМЕНЬШАЕТСЯ (ответ больше не unread)

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
  `processed()`, `forUser(User)`, `unreadAnswers()` (ответы с
  `answer_read_at=null`), методы `markInProgress()`,
  `markClosed(int $adminId, string $adminComment)`, `markAnswerRead()`,
  `markDeleted()`,
  `isNew()`, `isInProgress()`, `isClosed()`, `isDeleted()`, `isAnswerUnread()`,
  relation `user()` → `belongsTo(User)->withTrashed()`, relation `admin()` →
  `belongsTo(User)`, fillable `admin_comment, admin_id`, casts
  `closed_at, answer_read_at`
- `app/Http/Controllers/TicketController.php` — CRUD операции, вкладки
  «Новые»/«Обработанные» (query-параметр `?scope=`), счётчики в табах, eager
  load `->with('user.role', 'admin.role')` (устранение N+1), методы `start()`,
  `close()` с валидацией admin_comment (required, max 5000), `show()` — пометка
  ответа прочитанным для автора
- `app/Policies/TicketPolicy.php` — правила авторизации (viewAny/create: true,
  view: автор ИЛИ isAdmin, start: isAdmin + статус new, close: isAdmin +
  статус [new, in_progress], delete: isAdmin + статус [new, in_progress])
- `app/Services/TicketService.php` — бизнес-логика: `start()` (с guard и
  логированием), `close(int $adminId, string $adminComment)` (с guard на статус
  in_progress + trim комментария, логированием и сохранением + вызов
  `notifyAuthorOfAnswer()`), `notifyAuthorOfAnswer()` — приватный метод для
  уведомления автора (TG/MAX через NotificationService), `delete()` (с guard
  и логированием), возвращает `false` при неверном статусе или пустом
  комментарии (defence in depth)
- `app/Services/UserService.php` — метод `translateRoleName(string $roleName)` —
  перевод роли на русский (для бейджей в карточке тикета)
- `app/Services/NotificationService.php` — метод `notify(User $user, string
  $text, bool queued)` — отправка уведомлений в TG/MAX (используется для
  уведомления автора об ответе)
- `app/Http/Requests/StoreTicketRequest.php` — валидация создания (description
  required/max:5000, page_url nullable/url/max:500, screenshot
  nullable/image/max:5120)
- `database/factories/TicketFactory.php` — фабрика для тестов, state
  `inProgress()` для статуса in_progress
-
`database/migrations/2026_06_30_053314_add_admin_comment_to_tickets_table.php` —
миграция добавления поля admin_comment

-

`database/migrations/2026_07_21_120319_add_admin_id_and_answer_read_at_to_tickets_table.php`
— миграция добавления полей admin_id и answer_read_at
- `routes/tickets.php` — роуты (префикс megatulle, middleware auth БЕЗ
  require_open_shift), новый роут `tickets.start` для перевода в работу
- `resources/views/tickets/index.blade.php` — список с вкладками и счётчиками,
  заголовок страницы `'Тикеты'`
- `resources/views/tickets/create.blade.php` — форма создания
- `resources/views/tickets/show.blade.php` — детальная страница тикета с
  проверкой `Storage::disk('public')->exists($ticket->screenshot)` (показ «Файл
  скриншота недоступен» если файл удалён), блок «Комментарий администратора» (
  виден если admin_comment есть), ФИО автора как ссылка на профиль с бейджем
  роли, подпись ответившего админа (ФИО + роль + дата), кнопки действий по
  статусам (start из new, close + admin_comment textarea required из
  in_progress, delete из не-deleted)
- `resources/views/layouts/app.blade.php` — кнопка создания тикета с
  предзаполнением URL и `aria-label="Сообщить о проблеме"` (в секции
  `@section('content_top_nav_right')`)
- `config/adminlte.php` — пункт меню «Тикеты» (key='support', text='support',
  позиция перед 'logs')
- `lang/vendor/adminlte/ru/menu.php` — перевод 'support' => 'Тикеты' для пункта
  меню
- `app/Providers/AppServiceProvider.php` — BuildingMenu listener для двух
  бейджей: новый тикеты (danger, админы) + непрочитанные ответы (success,
  сотрудники) через remove + addBefore('logs') логика

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
- **Правило бейджа меню для админов:** считается только статус `new`, тикеты в
  `in_progress` не требуют внимания, бейдж красный `danger`
- **Правило бейджа меню для сотрудников (2026-07-21):** показываются только
  непрочитанные ответы (зелёный `success`), условие: `status=closed` И
  `admin_id IS NOT NULL` И `answer_read_at IS NULL`
- Статус `deleted` — это финальная корзина, в которую можно попасть из `new` ИЛИ
  `in_progress` ИЛИ `closed` (НЕ линейный lifecycle)
- Поле `closed_at` заполняется только при переводе в статус `closed` через
  `markClosed()`
- **Правило закрытия:** закрыть тикет можно ТОЛЬКО из статуса `in_progress` (
  сначала `start`, потом `close`). Закрыть напрямую из `new` — нельзя (403).
- Поле `admin_comment` заполняется ОБЯЗАТЕЛЬНО при закрытии тикета (required,
  trim не пустой, max 5000), виден и автору, и админу
- **Поле admin_id (2026-07-21):** фиксируется какой админ ответил на тикет (
  проставляется при `markClosed()`), FK→users, nullable (может быть NULL для
  старых тикетов)
- **Поле answer_read_at (2026-07-21):** когда автор прочитал ответ админа,
  nullable, null = непрочитано (учитывается в бейдже непрочитанных ответов)
- **Уведомление автора (2026-07-21):** при закрытии тикета с ответом автор
  получает уведомление в TG ИЛИ MAX (queued), текст уведомления включает вопрос
  автора (обрезка 300), ответ админа, ФИО админа и ссылку на тикет
- Условие отправки уведомления: у юзера должен быть `tg_id` ИЛИ `max_id` (
  привязан хотя бы один мессенджер), иначе пропускается тихо
- **Пометка ответа прочитанным:** при просмотре тикета автором (НЕ админом)
  вызывается `markAnswerRead()` (проставляет `answer_read_at = now()`), бейдж
  непрочитанных уменьшается
- **ФИО автора в карточке (2026-07-21):** отображается как ссылка на профиль
  редактирования (`users.edit`), рядом бейдж роли на русском (через
  `UserService::translateRoleName`)
- **Подпись ответившего админа (2026-07-21):** под комментарием показывается
  «Ответил: {ФИО} ({роль}), {дата закрытия}», ФИО и роль переводятся на русский
- Сотрудник не может редактировать или удалять свои тикеты (только создавать и
  просматривать)
- **Защита от повторных операций:** `TicketPolicy` + `TicketService` —
  стартовать можно только из `new`,
  закрывать можно только из `in_progress` (НЕ из new!), удалять можно только из
  `[new, in_progress]`
- **Логирование аудита:** операции start/close/delete пишутся в канал `system`
  через
  `Log::info` с `ticket_id` и `admin_id`
- **N+1 устранён:** `TicketController@show` использует
  `->with('user.role', 'admin.role')` — eager load автора/админа и их ролей
- **Скриншот:** при удалении файла с диска на странице детализации показывается
  «Файл скриншота недоступен» вместо broken image
- **Upload скриншота:** индикатор «Загрузка...» при выборе файла убран (фриз
  браузера из-за антивируса/ОС, превью появляется через `URL.createObjectURL`
  после системной паузы)

## Связанные topics

- [user-management.md](user-management.md) — роли и доступ (админы vs
  сотрудники), проверка `isAdmin()` в policy, метод `translateRoleName()`
- [notifications.md](notifications.md) — архитектура TG/MAX рассылки,
  NotificationService::notify() для уведомления автора об ответе
- [max-integration.md](max-integration.md) — интеграция с MAX мессенджером (
  альтернатива Telegram для уведомлений)
- [logging-channels.md](logging-channels.md) — канал `system` для логирования
  операций close/delete (аудит)
