# UserFactory

## Модель

`App\Models\User` - Пользователь системы

## Генерируемые поля и их типы данных

| Поле                | Тип данных | Описание                | Метод генерации                     |
|---------------------|------------|-------------------------|-------------------------------------|
| `name`              | string     | Имя пользователя        | `fake()->name()`                    |
| `email`             | string     | Уникальный email адрес  | `fake()->unique()->safeEmail()`     |
| `email_verified_at` | datetime   | null                    | Время верификации email             | `now()` |
| `password`          | string     | Хешированный пароль     | `Hash::make('password')`            |
| `remember_token`    | string     | Токен для запоминания   | `Str::random(10)`                   |
| `role_id`           | integer    | null                    | ID роли пользователя                | `null` |
| `salary_rate`       | float      | Ставка заработной платы | `faker->randomFloat(2, 100, 10000)` |

## Особые значения и константы

- **Пароль по умолчанию**: `"password"` (хешированный)
- **Пароль** сохраняется в статической переменной `$password` для
  переиспользования
- **Ставка заработной платы**: генерируется случайным образом от 100 до 10,000 с
  2 decimal places

## Состояния (states)

### `unverified()`

Создает пользователя с неподтвержденным email.

```php
User::factory()->unverified()->create();
```

**Изменения:**

- `email_verified_at` устанавливается в `null`

## Связи с другими моделями

- **role_id** - может быть связан с моделью `Role` (устанавливается в `null` по
  умолчанию)

## Примеры использования

### Базовое использование

```php
// Создание пользователя
$user = User::factory()->create();

// Создание пользователя с определенным именем и email
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Создание с состоянием

```php
// Создание пользователя с неподтвержденным email
$user = User::factory()->unverified()->create();
```

### Массовое создание

```php
// Создание 10 пользователей
$users = User::factory()->count(10)->create();
```

### Создание с ролью

```php
// Создание администратора
$admin = User::factory()->create([
    'role_id' => Role::where('name', 'admin')->first()->id,
    'salary_rate' => 50000
]);
```

### В тестах

```php
// Использование в тестах
$user = User::factory()->create();

$this->actingAs($user)
    ->get('/dashboard')
    ->assertSuccessful();
```

### Дополнительные примеры

```php
// Создание пользователя с определенной ставкой
$user = User::factory()->create([
    'salary_rate' => 15000.50
]);

// Создание пользователя без email верификации
$user = User::factory()->unverified()->create();

// Создание пользователя и установка роли через связь
$role = Role::factory()->create();
$user = User::factory()->create(['role_id' => $role->id]);
```
