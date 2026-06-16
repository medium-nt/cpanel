# User Management — Управление пользователями

> Last reviewed: 2026-06-16

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

**Специализация ролей:**

- `ShiftService::SHIFT_ROLES` = ['seamstress', 'cutter', 'otk] — только эти роли
  привязаны к сменному графику
- Админы, менеджеры и кладовщики работают независимо от смен

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

## Ключевые файлы

- `app/Models/User.php` — модель пользователя (роли, связи с сменами)
- `app/Services/UserService.php` — фильтрация пользователей (`getFiltered()`
  метод)
- `app/Http/Controllers/UsersController.php` — страница списка (`index()` метод)
- `resources/views/users/index.blade.php` — UI с фильтрами и колонкой «Цех»
- `public/js/PageQueryParam.js` — утилита авто-применения параметров URL

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

## Связанные topics

- [shift-system.md](shift-system.md) — смены и цехи, графики работы
- [salary-system.md](salary-system.md) — оплата по ролям и сменам
- [order-lifecycle.md](order-lifecycle.md) — доступ к заказам по ролям
