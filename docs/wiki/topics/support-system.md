# Support System — Тикет-система «Тикеты»

> Last reviewed: 2026-06-28

## Обзор

Тикет-система «Тикеты» (ранее «Поддержка») позволяет сотрудникам сообщать о
проблемах в работе системы. Сотрудники создают тикеты с описанием проблемы,
прикрепляют скриншоты и указывают URL страницы, где возникла проблема.
Администраторы обрабатывают тикеты и закрывают их после решения.

## Как это работает

### Жизненный цикл тикета

```
new → closed (markClosed)
new → deleted (markDeleted)
closed → deleted (markDeleted)
```

**Lifecycle НЕ линейный:** deleted — это финальная корзина, в которую можно
попасть из любого статуса.

**Статусы тикета:**

- `new` — новый тикет, требует обработки (показывается в «Новые»)
- `closed` — закрыт администратором (показывается в «Обработанные»)
- `deleted` — удалён в корзину администратором (показывается в «Обработанные»)

**Методы переходов:**

- `markClosed()` — перевести в статус `closed`, проставляет `closed_at = now()`
- `markDeleted()` — перевести в статус `deleted` (корзина)
- `isClosed()` / `isDeleted()` — проверки статусов

### Создание тикета

**Доступ:** любой авторизованный сотрудник (без требования открытой смены)

**Поля:**

- `description` (text, required) — описание проблемы, max 5000 символов
- `page_url` (string, nullable) — URL страницы, где возникла проблема, max 500
- `screenshot` (file, nullable) — скриншот проблемы, только изображение, max 5MB

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
- Вкладка «Новые» (`scope=new`) — их собственные тикеты со статусом `new`
- Вкладка «Обработанные» (`scope=processed`) — их тикеты со статусами `closed`
  или `deleted`

**Администраторы видят:**

- Все тикеты (без фильтрации `forUser`)
- Вкладка «Новые» — все тикеты со статусом `new` (бейдж с количеством)
- Вкладка «Обработанные» — все тикеты со статусами `closed` или `deleted`

**Навигация:**

- `GET /megatulle/tickets?scope=new` — вкладка «Новые» (дефолт)
- `GET /megatulle/tickets?scope=processed` — вкладка «Обработанные»

**Счётчики в табах:**

- «Новые» — красный бейдж `badge-danger` с количеством
  `Ticket::opened()->count()` (фильтр по автору для сотрудников, все — для
  админов)
- «Обработанные» — серый бейдж `badge-secondary` с количеством
  `Ticket::processed()->count()` (фильтр по автору для сотрудников, все — для
  админов)
- Бейдж скрыт при `count() === 0`

### Обработка тикетов (администратор)

**Закрытие тикета:**

- `PUT /megatulle/tickets/{ticket}/close` (один `tickets`, не двойной!)
- Доступ: только `isAdmin()` И статус `new` (защита от повторного закрытия через
  `TicketPolicy`)
- Изменяет статус на `closed` + проставляет `closed_at = now()`
- Логирование:
  `Log::info('Тикет закрыт администратором', ['ticket_id' => $ticket->id, 'admin_id' => $admin->id])`

**Удаление в корзину:**

- `PUT /megatulle/tickets/{ticket}/delete` (один `tickets`, не двойной!)
- Доступ: только `isAdmin()` И статус НЕ `deleted` (защита от повторного
  удаления через `TicketPolicy`)
- Изменяет статус на `deleted` (НЕ удаляет запись из БД)
- Логирование:
  `Log::info('Тикет перемещён в корзину администратором', ['ticket_id' => $ticket->id, 'admin_id' => $admin->id])`

### Бейдж новых тикетов

**Показ в меню:**

- Пункт меню «Тикеты» (ранее «Поддержка») имеет бейдж с количеством тикетов в
  статусе `new`
- Бейдж показывается только администраторам И только когда `count() > 0`
- Количество — `Ticket::query()->where('status', Ticket::STATUS_NEW)->count()`

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

| Действие | Кто может                          | Правило                                                                                          |
|----------|------------------------------------|--------------------------------------------------------------------------------------------------|
| `view`   | Автор тикета ИЛИ админ             | `$user->id === $ticket->user_id \|\| $user->isAdmin()`                                           |
| `create` | Любой сотрудник                    | `true` (без требований смены)                                                                    |
| `close`  | Только админ И статус `new`        | `$user->isAdmin() && $ticket->status === Ticket::STATUS_NEW` (защита от повторного закрытия)     |
| `delete` | Только админ И статус НЕ `deleted` | `$user->isAdmin() && $ticket->status !== Ticket::STATUS_DELETED` (защита от повторного удаления) |
| `index`  | Сотрудник видит свои, админ — все  | scope `forUser` для не-админов                                                                   |

**Defence in depth:**

- `TicketService::close()` возвращает `false` если статус не `new` (дублирующая
  проверка на уровне сервиса)
- `TicketService::delete()` возвращает `false` если статус уже `deleted` (
  дублирующая проверка на уровне сервиса)

**ВАЖНО:** Роуты тикетов находятся под `auth` middleware, но БЕЗ
`require_open_shift` — создание тикетов доступно даже без открытой смены.

## Ключевые файлы

- `app/Models/Ticket.php` — модель тикета, константы
  `STATUS_NEW/CLOSED/DELETED`, scopes (`opened` НЕ `new`! для scope-new),
  `processed()`, `forUser(User)`, методы `markClosed()`, `markDeleted()`,
  `isClosed()`, `isDeleted()`, relation `user()` →
  `belongsTo(User)->withTrashed()`
- `app/Http/Controllers/TicketController.php` — CRUD операции, вкладки
  «Новые»/«Обработанные» (query-параметр `?scope=`), счётчики в табах, eager
  load `->with('user')` (устранение N+1 в таблице админа)
- `app/Policies/TicketPolicy.php` — правила авторизации (viewAny/create: true,
  view: автор ИЛИ isAdmin, close: isAdmin + статус new, delete: isAdmin + статус
  НЕ deleted)
- `app/Services/TicketService.php` — бизнес-логика: `close()` (с guard и
  логированием), `delete()` (с guard и логированием), возвращает `false` при
  неверном статусе (defence in depth)
- `app/Http/Requests/StoreTicketRequest.php` — валидация (description
  required/max:5000, page_url nullable/url/max:500, screenshot
  nullable/image/max:5120)
- `database/factories/TicketFactory.php` — фабрика для тестов
- `routes/tickets.php` — роуты (префикс megatulle, middleware auth БЕЗ
  require_open_shift)
- `resources/views/tickets/index.blade.php` — список с вкладками и счётчиками,
  заголовок страницы `'Тикеты'`
- `resources/views/tickets/create.blade.php` — форма создания
- `resources/views/tickets/show.blade.php` — детальная страница тикета с
  проверкой `Storage::disk('public')->exists($ticket->screenshot)` (показ «Файл
  скриншота недоступен» если файл удалён)
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
- Scope `opened()` (НЕ `new`!) используется для фильтрации статуса `new` (слово
  `new` зарезервировано в PHP)
- Query-параметр `?scope=new` вызывает scope `opened()` (только status=new),
  `?scope=processed` — scope `processed()` (closed + deleted)
- Статус `deleted` — это финальная корзина, в которую можно попасть из `new` ИЛИ
  `closed` (НЕ линейный lifecycle)
- Поле `closed_at` заполняется только при переводе в статус `closed` через
  `markClosed()`
- Сотрудник не может редактировать или удалять свои тикеты (только создавать и
  просматривать)
- **Защита от повторных операций:** `TicketPolicy` + `TicketService` — закрывать
  можно только статус `new`, удалять — любой кроме `deleted`
- **Логирование аудита:** операции close/delete пишутся в канал `system` через
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
