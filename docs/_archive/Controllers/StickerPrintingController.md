# StickerPrintingController

**Путь:** `app/Http/Controllers/StickerPrintingController.php`

**Описание:** Контроллер для управления печатью стикеров и рабочими сменами
сотрудников

**Зависимости:**

- `App\Models\User` - модель пользователя
- `App\Services\MarketplaceOrderItemService` - сервис элементов заказов
- `App\Services\ScheduleService` - сервис расписаний
- `App\Services\UserService` - сервис пользователей
- `Illuminate\Http\Request` - HTTP запросы
- `Illuminate\Support\Facades\Log` - логирование

---

## Методы контроллера

### index(Request $request)

- **Описание:** Главная страница печати стикеров и управления сменами
- **Параметры:** `$request` - HTTP запрос с фильтрами
- **Возвращает:** `Illuminate\View\View`
- **Обработка баркода:**
    - Ищет пользователя по баркоду
    - Перенаправляет с user_id в параметрах
- **Данные:**
    - `title` (string) - "Печать стикеров"
    - `userId` (int) - ID выбранного пользователя
    - `items` (Collection) - элементы для стикеровки
    - `users` (Collection) - список пользователей (роли 1,2,4,5)
    - `dates` (json) - даты для статистики
    - `seamstressesJson` (json) - данные по швеям
    - `days_ago` (int) - количество дней назад (0-28)
    - `workShift` (array) - информация о смене пользователя
- **View:** `sticker_printing`

### openCloseWorkShift(Request $request)

- **Описание:** Открытие/закрытие рабочей смены по штрихкоду
- **Параметры:** `$request` - HTTP запрос
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Проверки:**
    1. Наличие пользователя в базе
    2. Наличие штрихкода в запросе
    3. Соответствие штрихкода выбранному пользователю
    4. Время закрытия смены (не раньше окончания рабочего времени)
    5. Отсутствие повторного открытия смены за день
- **Логика:**
    - **Открытие смены:**
        - Проверка опоздания
        - Установка флага `shift_is_open = true`
        - Запись времени начала
        - Вызов `ScheduleService::openWorkShift()`
    - **Закрытие смены:**
        - Проверка правильности закрытия
        - Установка флага `shift_is_open = false`
        - Запись времени закрытия
        - Вызов `ScheduleService::closeWorkShift()`
- **Логирование:** Все действия логируются в канал `work_shift`
- **Редирект:** `route('sticker_printing')` с сообщением

### openCloseWorkShiftAdmin(User $user)

- **Описание:** Административное открытие/закрытие смены
- **Параметры:** `$user` - модель пользователя
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Права:** Доступно только администраторам
- **Логика:**
    - Принудительное открытие/закрытие смены
    - Автоматическая установка времени начала/конца
    - Вызов соответствующих методов `ScheduleService`
- **Логирование:** Записывает в `work_shift` канал о действии администратора
- **Редирект:** `route('home')` с сообщением

---

## Особенности реализации

1. **Штрихкод аутентификация:** Использование штрихкодов для открытия/закрытия
   смен
2. **Валидация времени:** Проверка времени закрытия смены относительно рабочего
   графика
3. **Детальное логирование:** Все операции логируются в специальный канал
4. **Статистика:** Получение данных по производительности сотрудников за период
5. **Фильтрация:** Пользователи фильтруются по ролям и исключаются тестовые
   аккаунты

---

## Права доступа

- `index` - доступ для авторизованных пользователей
- `openCloseWorkShift` - доступ для пользователей с баркодами
- `openCloseWorkShiftAdmin` - доступ только для администраторов

---

## Роуты

- `GET /sticker_printing` - `index` - главная страница
- `POST /open_close_work_shift` - `openCloseWorkShift` - управление сменой
- `POST /open_close_work_shift_admin/{user}` - `openCloseWorkShiftAdmin` -
  административное управление

---

## Сервисные зависимости

### UserService

- `getUserByBarcode($barcode)` - поиск пользователя по баркоду
- `isSecondShiftOpeningToday($user)` - проверка повторного открытия смены
- `checkWorkShiftClosure($user)` - проверка закрытия смены
- `checkLateStartWorkShift($user)` - проверка опоздания

### ScheduleService

- `openWorkShift($user)` - открытие смены
- `closeWorkShift($user)` - закрытие смены

### MarketplaceOrderItemService

- `getDatesByLargeSizeRating($daysAgo)` - даты для статистики
- `getItemsForLabeling($request)` - элементы для стикеровки
- `getSeamstressesLargeSizeRating($dates)` - рейтинг швей
