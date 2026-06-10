# AppServiceProvider

## Назначение провайдера

`AppServiceProvider` - это основной сервис-провайдер приложения, который
отвечает за начальную конфигурацию глобальных настроек и регистрацию основных
Gates для авторизации пользователей в системе.

**Путь:** `app/Providers/AppServiceProvider.php`

## Регистрация сервисов (метод register)

Метод `register` в текущей реализации пуст:

```php
public function register(): void
{
    //
}
```

Никакие сервисы в контейнере зависимостей не регистрируются на данном этапе.

## Загрузка приложения (метод boot)

Метод `boot` выполняет следующие действия:

### 1. Настройка пагинации

```php
Paginator::useBootstrapFive();
Paginator::useBootstrapFour();
```

- Настраивает использование стилей Bootstrap 5 и Bootstrap 4 для компонентов
  пагинации
- Позволяет корректно отображать пагинацию с использованием CSS-классов
  Bootstrap

### 2. Регистрация Gates для авторизации

Система определяет следующие правила доступа (Gates):

#### `is-admin`

```php
Gate::define('is-admin', function (User $user) {
    return $user->isAdmin();
});
```

- Проверяет, является ли пользователь администратором
- Использует метод `isAdmin()` модели User

#### `is-storekeeper`

```php
Gate::define('is-storekeeper', function (User $user) {
    return $user->isStorekeeper();
});
```

- Проверяет, является ли пользователь кладовщиком
- Использует метод `isStorekeeper()` модели User

#### `is-seamstress`

```php
Gate::define('is-seamstress', function (User $user) {
    return $user->isSeamstress();
});
```

- Проверяет, является ли пользователь швеёй
- Использует метод `isSeamstress()` модели User

#### `is-storekeeper-or-admin`

```php
Gate::define('is-storekeeper-or-admin', function (User $user) {
    return $user->isStorekeeper() || $user->isAdmin();
});
```

- Разрешает доступ для кладовщиков и администраторов
- Комбинированное правило для общих ресурсов

#### `is-seamstress-or-admin`

```php
Gate::define('is-seamstress-or-admin', function (User $user) {
    return $user->isSeamstress() || $user->isAdmin();
});
```

- Разрешает доступ для швей и администраторов
- Комбинированное правило для общих ресурсов

#### `is-admin-storekeeper-seamstress-cutter`

```php
Gate::define('is-admin-storekeeper-seamstress-cutter', function (User $user) {
    return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter();
});
```

- Разрешает доступ для всех основных ролей производства
- Включает: администраторов, кладовщиков, швей и закройщиков

#### `viewLogViewer`

```php
Gate::define('viewLogViewer', function (User $user) {
    return $user->isAdmin();
});
```

- Определяет доступ к просмотру логов приложения
- Только администраторы имеют доступ

#### `is-show-finance`

```php
Gate::define('is-show-finance', function (User $user) {
    return $user->is_show_finance;
});
```

- Проверяет право на просмотр финансовых разделов
- Использует свойство `is_show_finance` модели User

## Подключенные фасады и сервисы

### Используемые фасады:

- `Illuminate\Pagination\Paginator` - для настройки пагинации
- `Illuminate\Support\Facades\Gate` - для определения правил доступа
- `App\Models\User` - модель пользователя для проверок авторизации

## Публикуемые ресурсы

Провайдер не публикует какие-либо ресурсы.

## Команды

Провайдер не регистрирует команды Artisan.

## Особенности реализации

### 1. Ролевая модель доступа

Приложение использует мультирольную модель с основными ролями:

- Administrator (`isAdmin()`)
- Storekeeper (`isStorekeeper()`)
- Seamstress (`isSeamstress()`)
- Cutter (`isCutter()`)

### 2. Комбинированные Gates

Несколько Gates созданы для предоставления общего доступа нескольким ролям:

- `is-storekeeper-or-admin`
- `is-seamstress-or-admin`
- `is-admin-storekeeper-seamstress-cutter`

### 3. Специфические права доступа

- `viewLogViewer` - контролирует доступ к логам системы
- `is-show-finance` - управляет видимостью финансовых разделов

### 4. Двойная конфигурация пагинации

Использование обоих Bootstrap 4 и Bootstrap 5 обеспечивает совместимость с
различными версиями фреймворка в проекте.

## Рекомендации по использованию

Для проверки прав доступа в контроллерах или других частях приложения
используйте:

- `Gate::allows('gate-name')` - для проверки текущего пользователя
- `Gate::forUser($user)->allows('gate-name')` - для проверки конкретного
  пользователя
- `@can('gate-name')` - в Blade-шаблонах
- `Gate::authorize('gate-name')` - для автоматического выброса исключения при
  отсутствии прав

---
*Дата последнего изменения: 2025-12-05*
