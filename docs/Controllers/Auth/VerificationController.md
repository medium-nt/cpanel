# VerificationController

## Общее назначение

`VerificationController` отвечает за верификацию email адресов пользователей
после регистрации. Он управляет процессом подтверждения email через специальные
ссылки, отправляемые пользователям.

## Путь к файлу

`app/Http/Controllers/Auth/VerificationController.php`

## Наследование и трейты

- **Наследует:** `App\Http\Controllers\Controller`
- **Используемые трейты:**
    - `Illuminate\Foundation\Auth\VerifiesEmails` - предоставляет основную
      функциональность для верификации email

## Свойства

### $redirectTo

- **Тип:** `string`
- **Значение по умолчанию:** `'/home'`
- **Описание:** URL, куда будет перенаправлен пользователь после успешной
  верификации email

## Конструктор

### __construct()

- **Описание:** Инициализирует контроллер и применяет middleware
- **Middleware:**
    - `auth` - требует аутентификации пользователя для доступа к любым методам
      контроллера
    - `signed` - применяется только к методу `verify`, проверяет подпись URL для
      безопасности
    - `throttle:6,1` - применяется к методам `verify` и `resend`, ограничивает 6
      запросов в минуту

## Методы из трейта VerifiesEmails

Контроллер наследует следующие методы из трейта:

### show(Request $request)

- **Описание:** Отображает страницу с уведомлением о необходимости верификации
  email
- **Route:** `GET /email/verify`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос
- **Middleware:** `auth`
- **Возвращает:** `Illuminate\View\View`
- **View:** `auth.verify`
- **Логика:**
    - Если email уже верифицирован - перенаправляет на `$redirectTo`
    - Иначе отображает страницу с предложением проверить почту

### verify(Request $request)

- **Описание:** Обрабатывает верификацию email по подписанной ссылке
- **Route:** `GET /email/verify/{id}/{hash}`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос
- **Middleware:** `auth`, `signed`, `throttle:6,1`
- **Действия:**
    1. Авторизация пользователя (если ID в URL соответствует
       аутентифицированному пользователю)
    2. Проверка подписи URL
    3. Поиск пользователя по ID
    4. Проверка хеша email
    5. Если все проверки пройдены - помечает email как верифицированный
    6. Запускает событие `Verified`
- **Возвращает:** `Illuminate\Http\RedirectResponse`

### resend(Request $request)

- **Описание:** Повторно отправляет письмо для верификации email
- **Route:** `POST /email/verification-notification`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос
- **Middleware:** `auth`, `throttle:6,1`
- **Действия:**
    1. Проверяет, не верифицирован ли уже email
    2. Отправляет письмо с новой ссылкой для верификации
    3. Возвращает JSON response
- **Возвращает:** `Illuminate\Http\JsonResponse`

## Поток выполнения

### Первичная верификация

1. Пользователь регистрируется в системе
2. Если включена верификация (MustVerifyEmail), система отправляет письмо со
   ссылкой
3. При попытке доступа к защищенным ресурсам пользователь перенаправляется на
   `/email/verify`
4. Пользователь переходит по ссылке из письма
5. Система верифицирует email и перенаправляет на `$redirectTo`

### Повторная отправка

1. Пользователь на странице верификации нажимает кнопку "Отправить повторно"
2. Система отправляет новую ссылку на верификацию
3. Отображается сообщение об успешной отправке

## Настройки

### Включение верификации

Для активации функционала верификации необходимо:

1. В модели User реализовать интерфейс `MustVerifyEmail`:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    // ...
}
```

2. Добавить поле `email_verified_at` в миграцию пользователей:

```php
$table->timestamp('email_verified_at')->nullable();
```

### Конфигурация почты

В `config/mail.php` необходимо настроить:

- Драйвер отправки
- SMTP настройки (для production)
- Адрес отправителя

## URL маршруты

Стандартные маршруты верификации (определяются в `routes/web.php`):

```php
Auth::routes(['verify' => true]);
```

Созданные маршруты:

- `GET /email/verify` - страница с уведомлением о необходимости верификации
- `GET /email/verify/{id}/{hash}` - обработка верификации по ссылке
- `POST /email/verification-notification` - повторная отправка письма

## Безопасность

1. **Signed URLs:** Ссылки верификации имеют криптографическую подпись,
   предотвращающую подделку
2. **Throttling:** Ограничение 6 запросов в минуту для предотвращения спама
3. **Hash verification:** Хеш email в URL подтверждает корректность адреса
4. **ID проверка:** Сравнивает ID из URL с аутентифицированным пользователем
5. **Время жизни:** Ссылки имеют ограниченное время действия (настраивается)

## Email уведомления

По умолчанию используется класс `Illuminate\Auth\Notifications\VerifyEmail`.

### Кастомизация уведомления

```php
<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Подтвердите ваш Email')
            ->line('Нажмите на кнопку ниже для подтверждения вашего email адреса.')
            ->action('Подтвердить Email', $url)
            ->line('Если вы не создавали аккаунт, проигнорируйте это письмо.');
    }
}
```

### Установка кастомного уведомления в модели User

```php
public function sendEmailVerificationNotification()
{
    $this->notify(new \App\Notifications\VerifyEmail);
}
```

## Middleware защиты

Для защиты маршрутов, требующих верифицированного email:

```php
Route::get('/dashboard', function () {
    // Только для верифицированных пользователей
})->middleware(['auth', 'verified']);
```

## События

- `Illuminate\Auth\Events\Verified` - генерируется при успешной верификации
  email

### Обработка события

```php
// App\Providers\EventServiceProvider

protected $listen = [
    'Illuminate\Auth\Events\Verified' => [
        'App\Listeners\LogVerifiedUser',
    ],
];
```
