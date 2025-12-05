# MovementMaterialToWorkshopController

**Путь:** `app/Http/Controllers/MovementMaterialToWorkshopController.php`

## Общее назначение

Контроллер для управления перемещением материалов со склада в производственный
цех. Обеспечивает полный жизненный цикл заказов: создание, сборку, приемку и
списание материалов.

## Зависимости и сервисы

- `App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest` - валидация
  сборки поставки
- `App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest` - валидация
  списания
- `App\Http\Requests\StoreMovementMaterialToWorkshopRequest` - валидация
  создания заказа
- `App\Models\Material` - модель материала
- `App\Models\Order` - модель заказа
- `App\Services\MovementMaterialToWorkshopService` - сервис обработки
  перемещений
- `App\Services\TgService` - сервис отправки сообщений в Telegram
- `App\Services\UserService` - сервис работы с пользователями
- `Illuminate\Support\Facades\Log` - фасад для логирования

## Список методов

### index(Request $request)

- **Описание:** Отображение списка заказов на перемещение материалов с
  фильтрацией по статусу
- **URL:** `/movements_to_workshop`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_to_workshop.index`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `orders` (LengthAwarePaginator) - коллекция заказов с сохранением
      параметров фильтрации
- **Параметры запроса:**
    - `status` (int, optional) - фильтр по статусу заказа
- **Фильтрация:** Через
  `MovementMaterialToWorkshopService::getOrdersByStatus($request->status)`

### create()

- **Описание:** Форма создания нового заказа на перемещение материалов в цех
- **URL:** `/movements_to_workshop/create`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_to_workshop.create`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `materials` (Collection) - список всех материалов

### store(StoreMovementMaterialToWorkshopRequest $request)

- **Описание:** Сохранение нового заказа на перемещение материалов
- **URL:** `/movements_to_workshop`
- **Метод:** POST
- **Параметры:** Валидируются через `StoreMovementMaterialToWorkshopRequest`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке сервиса: `back()->withErrors(['error' => 'Внутренняя ошибка'])`
- **Успешный редирект:** `route('movements_to_workshop.index')`
- **Flash сообщение:** `success` - 'Заказ сформирован и отправлен на склад'
- **Делегирование:** Бизнес-логика вынесена в
  `MovementMaterialToWorkshopService::store()`

### collect(Order $order)

- **Описание:** Форма сборки поставки для конкретного заказа
- **URL:** `/movements_to_workshop/{order}/collect`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_to_workshop.collect`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `order` (Order) - заказ для сборки

### write_off()

- **Описание:** Форма списания материалов
- **URL:** `/movements_to_workshop/write_off`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_to_workshop.write_off`
- **Данные:**
    - `title` (string) - заголовок страницы

### save_write_off(SaveWriteOffMovementMaterialToWorkshopRequest $request)

- **Описание:** Сохранение операции списания материалов
- **URL:** `/movements_to_workshop/save_write_off`
- **Метод:** POST
- **Параметры:** Валидируются через
  `SaveWriteOffMovementMaterialToWorkshopRequest`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке сервиса: `back()->withErrors(['error' => 'Внутренняя ошибка'])`
- **Успешный редирект:** `route('inventory.workshop')`
- **Flash сообщение:** `success` - 'Материал списан'
- **Делегирование:** Бизнес-логика вынесена в
  `MovementMaterialToWorkshopService::save_write_off()`

### save_collect(SaveCollectMovementMaterialToWorkshopRequest $request, Order $order)

- **Описание:** Сохранение собранной поставки
- **URL:** `/movements_to_workshop/{order}/save_collect`
- **Метод:** POST
- **Параметры:** Валидируются через
  `SaveCollectMovementMaterialToWorkshopRequest`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке сервиса: `back()->withErrors(['error' => 'Внутренняя ошибка'])`
- **Успешный редирект:** `route('movements_to_workshop.index')`
- **Flash сообщение:** `success` - 'Отгрузка сформирована'
- **Делегирование:** Бизнес-логика вынесена в
  `MovementMaterialToWorkshopService::save_collect()`

### receive(Order $order)

- **Описание:** Форма приемки поставки в цехе
- **URL:** `/movements_to_workshop/{order}/receive`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_to_workshop.receive`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `order` (Order) - принимаемый заказ

### save_receive(Request $request, Order $order)

- **Описание:** Подтверждение приемки поставки с уведомлением в Telegram
- **URL:** `/movements_to_workshop/{order}/save_receive`
- **Метод:** POST
- **Действия:**
    1. Обновление статуса заказа на 3 (выполнен)
    2. Установка времени завершения
    3. Формирование списка материалов
    4. Отправка уведомлений в Telegram
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('movements_to_workshop.index')`
- **Flash сообщение:** `success` - 'Поставка принята'
- **Уведомления:**
    - Администратору (`config('telegram.admin_id')`)
    - Работающим швеям (`UserService::getListSeamstressesWorkingToday()`)
    - Работающим кладовщикам (`UserService::getListStorekeepersWorkingToday()`)
- **Логирование:** Запись в канал 'erp'

### delete(Order $order)

- **Описание:** Удаление заказа (только для заказов со статусом 0)
- **URL:** `/movements_to_workshop/{order}/delete`
- **Метод:** GET/DELETE
- **Проверки:**
    - `status != 0` - нельзя удалить заказ в работе
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('movements_to_workshop.index')`
- **Flash сообщения:**
    - `error` - 'Невозможно удалить заказ, так как он уже в работе'
    - `success` - 'Поставка удалена'
- **Действия:**
    1. Удаление связанных `movementMaterials`
    2. Удаление заказа

## Работа с моделями данных

- **Order:** Основная модель для хранения информации о перемещениях
- **Material:** Справочник материалов
- **MovementMaterial:** Связанная модель для отслеживания движения материалов

## Статусы заказов

- `0` - новый (можно удалять)
- `1` - в работе
- `2` - собран
- `3` - принят/выполнен

## Типы движения

- `type_movement = 2` - перемещение материала в цех

## Валидация

Используются специализированные классы валидации:

- `StoreMovementMaterialToWorkshopRequest` - создание заказа
- `SaveCollectMovementMaterialToWorkshopRequest` - сборка поставки
- `SaveWriteOffMovementMaterialToWorkshopRequest` - списание

## Права доступа

- Контроллер не использует Gate или middleware для проверки прав доступа
- Предполагается использование глобальных middleware для аутентификации
- Использует `auth()->user()` для получения текущего пользователя

## Особенности реализации

- Вся бизнес-логика вынесена в сервис `MovementMaterialToWorkshopService`
- Интеграция с Telegram для уведомлений сотрудников
- Журналирование операций в канале 'erp'
- Сложный жизненный цикл заказов с множеством статусов
- Разграничение прав на удаление по статусам заказов
