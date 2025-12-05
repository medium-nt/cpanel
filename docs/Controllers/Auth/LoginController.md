# LoginController

## Общее назначение

`LoginController` отвечает за аутентификацию пользователей в приложении. Он
управляет процессом входа, включая отображение формы входа, проверку учетных
данных и управление сессиями.

## Путь к файлу

`app/Http/Controllers/Auth/LoginController.php`

## Наследование и трейты

- **Наследует:** `App\Http\Controllers\Controller`
- **Используемые трейты:**
    - `Illuminate\Foundation\Auth\AuthenticatesUsers` - предоставляет основную
      функциональность аутентификации

## Свойства

### $redirectTo

- **Тип:** `string`
- **Значение по умолчанию:** `'/home'`
- **Описание:** URL, куда будет перенаправлен пользователь после успешного входа

## Конструктор

### __construct()

- **Описание:** Инициализирует контроллер и применяет middleware
- **Middleware:**
    - `guest` - применяется ко всем методам, кроме `logout` (гостям разрешен
      доступ)
    - `auth` - применяется только к методу `logout` (требует аутентификации)

## Методы из трейта AuthenticatesUsers

Контроллер наследует следующие основные методы из трейта:

### showLoginForm()

- **Описание:** Отображает форму входа
- **Route:** `GET /login`
- **Возвращает:** `Illuminate\View\View`
- **View:** `auth.login`

### login(Request $request)

- **Описание:** Обрабатывает попытку входа в систему
- **Route:** `POST /login`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос с учетными данными
- **Валидация (по умолчанию):**
    - `email` - required|string
    - `password` - required|string
- **Возвращает:**
    - При успехе: `Illuminate\Http\RedirectResponse` на `$redirectTo`
    - При ошибке: `Illuminate\Http\RedirectResponse` обратно к форме с ошибками

### logout(Request $request)

- **Описание:** Выполняет выход пользователя из системы
- **Route:** `POST /logout`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос
- **Действия:**
    - Инвалидирует сессию пользователя
    - Удаляет cookie сессии
    - Генерирует новый токен CSRF
    - Выполняет logout из guard
- **Возвращает:** `Illuminate\Http\RedirectResponse` на `/`

### username()

- **Описание:** Возвращает имя поля для аутентификации
- **Возвращает:** `string` (по умолчанию 'email')
- **Примечание:** Можно переопределить для использования другого поля (
  например, 'login')

### guard()

- **Описание:** Возвращает используемый guard для аутентификации
- **Возвращает:** `Illuminate\Contracts\Auth\StatefulGuard`
- **По умолчанию:** `web` guard

### hasTooManyLoginAttempts(Request $request)

- **Описание:** Проверяет, превышено ли количество попыток входа
- **Возвращает:** `bool`

### incrementLoginAttempts(Request $request)

- **Описание:** Увеличивает счетчик попыток входа
- **Использует:** Cache или Session для хранения счетчика

### clearLoginAttempts(Request $request)

- **Описание:** Сбрасывает счетчик попыток входа
- **Вызывается:** После успешного входа

### throttleKey(Request $request)

- **Описание:** Генерирует ключ для throttling
- **Возвращает:** `string` - уникальный ключ на основе IP и email/username

## Поток выполнения

1. Пользователь переходит на страницу `/login`
2. Вводит email и пароль в форму
3. Система валидирует входные данные
4. Проверяется throttling (слишком много попыток)
5. Аутентификация через guard
6. При успехе:
    - Создается сессия
    - Запоминается пользователь (если требуется)
    - Сбрасываются счетчики попыток
    - Перенаправление на `$redirectTo`
7. При ошибке:
    - Увеличивается счетчик попыток
    - Перенаправление обратно с сообщением об ошибке

## Настройки и возможности кастомизации

### Изменение поля входа

```php
public function username()
{
    return 'login'; // вместо email
}
```

### Добавление дополнительной валидации

```php
protected function validateLogin(Request $request)
{
    $request->validate([
        $this->username() => 'required|string',
        'password' => 'required|string',
        'captcha' => 'required|captcha',
    ]);
}
```

### Custom redirect после входа

```php
protected function redirectTo()
{
    return '/dashboard';
}
```

### Custom response при успехе

```php
protected function sendLoginResponse(Request $request)
{
    // Custom logic
    return parent::sendLoginResponse($request);
}
```

## Безопасность

1. **CSRF защита:** Все формы включают CSRF токен
2. **Throttling:** Защита от брутфорс атак
3. **Hashing паролей:** Пароли никогда не хранятся в открытом виде
4. **Session security:** Безопасная обработка сессий

## Middleware

- `guest` - предотвращает доступ аутентифицированных пользователей к форме входа
- `auth` - требуется для выхода из системы

## Session данные

При успешном входе:

- `auth` guards сохраняют ID пользователя
- Создается remember_token если выбрана опция "Запомнить меня"
- Session ID регенерируется для безопасности
