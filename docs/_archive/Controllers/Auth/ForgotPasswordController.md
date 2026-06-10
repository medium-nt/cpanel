# ForgotPasswordController

## Общее назначение

`ForgotPasswordController` отвечает за обработку запросов на восстановление
пароля. Он управляет процессом отправки ссылок для сброса пароля на email
пользователя.

## Путь к файлу

`app/Http/Controllers/Auth/ForgotPasswordController.php`

## Наследование и трейты

- **Наследует:** `App\Http\Controllers\Controller`
- **Используемые трейты:**
    - `Illuminate\Foundation\Auth\SendsPasswordResetEmails` - предоставляет
      функциональность для отправки email с инструкциями по восстановлению
      пароля

## Особенности реализации

1. **Минимальная конфигурация:** Контроллер полностью relies на трейт
   `SendsPasswordResetEmails`
2. **Без дополнительной логики:** Не определяет никаких собственных свойств или
   методов
3. **Стандартный функционал:** Использует стандартные Laravel механизмы для
   восстановления пароля

## Методы из трейта SendsPasswordResetEmails

Контроллер наследует следующие методы из трейта:

### showLinkRequestForm()

- **Описание:** Отображает форму для ввода email адреса
- **Route:** `GET /password/reset`
- **Возвращает:** `Illuminate\View\View`
- **View:** `auth.passwords.email`

### sendResetLinkEmail(Request $request)

- **Описание:** Обрабатывает отправку формы и отправляет email со ссылкой для
  сброса пароля
- **Route:** `POST /password/email`
- **Параметры:**
    - `$request` (Illuminate\Http\Request) - HTTP запрос с email адресом
- **Валидация:**
    - `email` - required|email|exists:users,email
- **Возвращает:**
    - При успехе: `Illuminate\Http\RedirectResponse` с сообщением об отправке
      ссылки
    - При ошибке: `Illuminate\Http\RedirectResponse` обратно к форме с ошибками

### broker()

- **Описание:** Возвращает экземпляр password broker
- **Возвращает:** `Illuminate\Contracts\Auth\PasswordBroker`

## Поток выполнения

1. Пользователь переходит на страницу `/password/reset`
2. Вводит свой email адрес в форму
3. Система проверяет существование email в базе данных
4. Если email существует, генерируется уникальный токен
5. На email отправляется ссылка с токеном для сброса пароля
6. Пользователь переходит по ссылке для установки нового пароля

## Настройки

### Конфигурационные параметры (config/auth.php)

- **passwords.users.table** - таблица пользователей (по умолчанию: users)
- **passwords.users.email** - view для email уведомления
- **passwords.users.expire** - время жизни токена в минутах (по умолчанию: 60)
- **passwords.users.throttle** - количество попыток до throttling

### Email уведомления

Email для сброса пароля использует view `auth.passwords.reset` по умолчанию.

## Безопасность

1. **Токены:** Уникальные токены создаются для каждого запроса
2. **Throttling:** Ограничение количества попыток для предотвращения спама
3. **Проверка email:** Система проверяет существование email перед отправкой
4. **Время жизни:** Токены имеют ограниченное время действия

## Связь с ResetPasswordController

`ForgotPasswordController` работает в паре с `ResetPasswordController`:

- `ForgotPasswordController` - отправляет email с токеном
- `ResetPasswordController` - обрабатывает установку нового пароля по токену
