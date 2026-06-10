# ResetPasswordController

## Общее назначение

`ResetPasswordController` отвечает за обработку запросов на сброс пароля
пользователя. Он управляет процессом установки нового пароля по ссылке,
полученной через email.

## Путь к файлу

`app/Http/Controllers/Auth/ResetPasswordController.php`

## Наследование и трейты

- **Наследует:** `App\Http\Controllers\Controller`
- **Используемые трейты:**
    - `Illuminate\Foundation\Auth\ResetsPasswords` - предоставляет основную
      функциональность для сброса пароля

## Свойства

### $redirectTo

- **Тип:** `string`
- **Значение по умолчанию:** `'/home'`
- **Описание:** URL, куда будет перенаправлен пользователь после успешного
  сброса пароля

## Особенности реализации

1. **Минимальная конфигурация:** Контроллер полностью relies на трейт
   `ResetsPasswords`
2. **Без дополнительной логики:** Не определяет никаких собственных методов
   кроме конструктора
3. **Стандартный функционал:** Использует стандартные Laravel механизмы для
   сброса пароля

## Методы из трейта ResetsPasswords

Контроллер наследует следующие методы из трейта:

### showResetForm(Request $request, $token = null)

- **Описание:** Отображает форму сброса пароля
- **Route:** `GET /password/reset/{token}`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос
    - `$token` (string|null) - токен сброса пароля из URL
- **Возвращает:** `Illuminate\View\View`
- **View:** `auth.passwords.reset`
- **Передаваемые данные:**
    - `token` - токен сброса пароля
    - `email` - email пользователя из сессии

### reset(Request $request)

- **Описание:** Обрабатывает отправку формы сброса пароля
- **Route:** `POST /password/reset`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос с данными формы
- **Валидация:**
    - `token` - required
    - `email` - required|email
    - `password` - required|confirmed|min:8
- **Возвращает:**
    - При успехе: `Illuminate\Http\RedirectResponse` на `$redirectTo`
    - При ошибке: `Illuminate\Http\RedirectResponse` обратно к форме с ошибками
- **Действия:**
    1. Валидация входных данных
    2. Получение пользователя по email и токену
    3. Сброс пароля
    4. Аутентификация пользователя с новым паролем
    5. Инвалидация всех других сессий пользователя
    6. Генерация нового remember_token

### broker()

- **Описание:** Возвращает экземпляр password broker
- **Возвращает:** `Illuminate\Contracts\Auth\PasswordBroker`

### rules()

- **Описание:** Возвращает правила валидации для формы сброса
- **Возвращает:** `array`

## Поток выполнения

1. Пользователь получает email со ссылкой для сброса пароля (от
   ForgotPasswordController)
2. Переходит по ссылке, содержащей токен
3. Отображается форма сброса пароля с предзаполненным email
4. Пользователь вводит новый пароль и подтверждение
5. Система:
    - Валидирует данные
    - Проверяет корректность токена
    - Находит пользователя по email
    - Устанавливает новый пароль (хешируя его)
    - Аутентифицирует пользователя
    - Удаляет использованный токен
    - Инвалидирует старые сессии
6. Перенаправляет пользователя на домашнюю страницу

## Настройки

### Конфигурационные параметры (config/auth.php)

- **passwords.users.table** - таблица пользователей (по умолчанию: users)
- **passwords.users.email** - view для email уведомления
- **passwords.users.expire** - время жизни токена в минутах (по умолчанию: 60)
- **passwords.users.throttle** - количество попыток до throttling

### Структура таблицы password_resets

```sql
- email (string)
- token (string, hashed)
- created_at (timestamp)
```

## Безопасность

1. **Токены:** Хешируются перед сохранением в базу данных
2. **Время жизни:** Токены имеют ограниченное время действия
3. **Уникальность:** Каждый токен уникален для каждого запроса
4. **Инвалидация сессий:** При сбросе пароля все текущие сессии пользователя
   становятся недействительными
5. **Throttling:** Ограничение количества попыток для предотвращения атак
6. **CSRF защита:** Все формы включают CSRF токен

## Связь с ForgotPasswordController

`ResetPasswordController` работает в паре с `ForgotPasswordController`:

1. `ForgotPasswordController`:
    - Принимает email от пользователя
    - Создает и сохраняет токен в таблице password_resets
    - Отправляет email со ссылкой на сброс пароля

2. `ResetPasswordController`:
    - Принимает токен и новый пароль
    - Валидирует и проверяет токен
    - Устанавливает новый пароль
    - Удаляет использованный токен

## Возможности кастомизации

### Изменение правил валидации

```php
protected function rules()
{
    return [
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|confirmed|min:12|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
    ];
}
```

### Custom response после сброса

```php
protected function sendResetResponse(Request $request, $response)
{
    // Additional logic after successful reset
    return redirect($this->redirectPath())
                    ->with('status', trans($response));
}
```

### Custom сообщение об ошибке

```php
protected function sendResetFailedResponse(Request $request, $response)
{
    return redirect()->back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => trans($response)]);
}
```

## Расширения

### История паролей

```php
protected function resetPassword($user, $password)
{
    // Сохраняем старый пароль в историю
    PasswordHistory::create([
        'user_id' => $user->id,
        'password' => $user->password,
    ]);

    $user->password = Hash::make($password);
    $user->save();

    $this->guard()->login($user);
}
```

### Force password change

```php
public function showResetForm(Request $request, $token = null)
{
    return view('auth.passwords.reset')->with(
        ['token' => $token, 'email' => $request->email]
    )->with('force_change', true);
}
```
