# UsersController

**Путь:** `app/Http/Controllers/UsersController.php`

**Описание:** Контроллер для управления пользователями, их профилями, мотивацией
и ставками

**Зависимости:**

- `App\Http\Requests\MotivationUpdateUsersRequest` - валидация мотивации
- `App\Http\Requests\RateUpdateUsersRequest` - валидация ставок
- `App\Http\Requests\StoreUsersRequest` - валидация создания
- `App\Models\Material` - модель материалов
- `App\Models\Motivation` - модель мотивации
- `App\Models\User` - модель пользователя
- `App\Services\ScheduleService` - сервис расписаний
- `App\Services\TgService` - сервис Telegram
- `App\Services\UserService` - сервис пользователей
- Laravel аутентификация и валидация

---

## Методы контроллера

### index()

- **Описание:** Отображение списка всех пользователей
- **Политика:** `viewAny` - проверка прав на просмотр пользователей
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `users` (LengthAwarePaginator) - пагинированный список пользователей (10
      на страницу)
    - `title` (string) - "Пользователи"
- **View:** `users.index`

### create()

- **Описание:** Форма создания нового пользователя
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Добавить сотрудника"
- **View:** `users.create`

### store(StoreUsersRequest $request)

- **Описание:** Сохранение нового пользователя
- **Параметры:** `$request` - валидированные данные
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:** Массовое создание пользователя через `create()`
- **Редирект:** `route('users.index')`

### edit(User $user)

- **Описание:** Страница редактирования пользователя
- **Параметры:** `$user` - модель пользователя (Route Model Binding)
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Изменить пользователя"
    - `user` (User) - модель пользователя
    - `events` (Collection) - расписание пользователя
    - `motivations` (Collection) - мотивация пользователя
    - `isBeforeStartWorkDay` (bool) - проверка времени начала работы
    - `materials` (Collection) - доступные материалы (type_id = 1)
    - `selectedMaterials` (array) - выбранные материалы пользователя
    - `rates` (Collection) - ставки пользователя
- **View:** `users.edit`

### update(Request $request, User $user)

- **Описание:** Обновление данных пользователя
- **Параметры:**
    - `$request` - HTTP запрос
    - `$user` - модель пользователя
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Использует:** `UserService::saved()`
- **Логика:** Делегирует сохранение сервисному слою

### destroy(User $user)

- **Описание:** Удаление пользователя
- **Параметры:** `$user` - модель пользователя
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:** Мягкое удаление пользователя

### profile(Request $request)

- **Описание:** Страница профиля авторизованного пользователя
- **Параметры:** `$request` - HTTP запрос
- **Особенности:**
    - Обрабатывает `tg_id` для привязки Telegram
    - Отправляет приветственное сообщение в Telegram
    - Логирует привязку в канал `tg_api`
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Профиль"
    - `user` (User) - авторизованный пользователь
- **View:** `users.profile`

### profileUpdate(Request $request)

- **Описание:** Обновление профиля пользователя
- **Параметры:** `$request` - HTTP запрос
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Использует:** `UserService::saved()` для обновления

### disconnectTg()

- **Описание:** Отвязка Telegram аккаунта
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:** Очищает поле `tg_id` и логирует действие
- **Логирование:** Канал `tg_api`

### autologin(string $email)

- **Описание:** Автоматический вход по email (только для локальной разработки)
- **Параметры:** `$email` - email пользователя
- **Ограничения:** Только для `local` окружения
- **Логика:**
    - Ищет пользователя по email
    - Авторизует через `Auth::login()`
- **Редирект:** `/home`

### motivationUpdate(MotivationUpdateUsersRequest $request, User $user)

- **Описание:** Обновление таблицы мотивации пользователя
- **Параметры:**
    - `$request` - валидированные данные мотивации
    - `$user` - модель пользователя
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:**
    1. Удаляет существующие записи мотивации
    2. Создает новые записи из массивов данных
    3. Сохраняет периоды и бонусы

### rateUpdate(RateUpdateUsersRequest $request, User $user, UserService $userService)

- **Описание:** Обновление ставок пользователя по материалам
- **Параметры:**
    - `$request` - валидированные данные ставок
    - `$user` - модель пользователя
    - `$userService` - сервис пользователей
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Использует:** `UserService::updateUserMaterialRates()`

### getBarcode(User $user)

- **Описание:** Генерация PDF с штрихкодом пользователя
- **Параметры:** `$user` - модель пользователя
- **Возвращает:** `Illuminate\View\View` (для PDF)
- **View:** `pdf.user_barcode`
- **Использование:** Генерация штрихкода для печати

---

## Особенности реализации

1. **Policy:** Используются политики для проверки прав доступа
2. **Service Layer:** Основная логика вынесена в `UserService`
3. **Form Requests:** Различные классы для валидации разных типов данных
4. **Telegram Integration:** Полноценная интеграция с Telegram
5. **Route Model Binding:** Автоматическая подгрузка моделей
6. **Motivation System:** Сложная система мотивации с периодами

---

## Права доступа

- `index`, `create`, `store`, `edit`, `update`, `destroy` - требуются
  административные права
- `profile`, `profileUpdate`, `disconnectTg` - доступны авторизованному
  пользователю
- `autologin` - только для разработки

---

## Роуты

- `GET /users` - `index` - список пользователей
- `GET /users/create` - `create` - форма создания
- `POST /users` - `store` - сохранение
- `GET /users/{user}/edit` - `edit` - редактирование
- `PUT/PATCH /users/{user}` - `update` - обновление
- `DELETE /users/{user}` - `destroy` - удаление
- `GET /profile` - `profile` - профиль пользователя
- `PUT/PATCH /profile` - `profileUpdate` - обновление профиля
- `POST /disconnect-tg` - `disconnectTg` - отвязка Telegram
- `GET /autologin/{email}` - `autologin` - авто-вход
- `POST /users/{user}/motivation-update` - `motivationUpdate` - обновление
  мотивации
- `POST /users/{user}/rate-update` - `rateUpdate` - обновление ставок
- `GET /users/{user}/barcode` - `getBarcode` - штрихкод

---

## Сервисные зависимости

### UserService

- `saved($request, $user)` - сохранение данных пользователя
- `getMotivationByUserId($userId)` - получение мотивации
- `getRateByUserId($userId)` - получение ставок
- `updateUserMaterialRates($user, $request)` - обновление ставок
- `translateRoleName($roleName)` - перевод роли

### ScheduleService

- `getScheduleByUserId($userId)` - получение расписания
- `isBeforeStartWorkDay($user)` - проверка времени

### TgService

- `sendMessage($chatId, $message)` - отправка сообщений
