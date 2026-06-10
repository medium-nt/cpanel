# DefectMaterialController

**Путь:** `app/Http/Controllers/DefectMaterialController.php`

## Общее назначение

Контроллер для управления бракованными и излишними материалами. Обеспечивает
полный жизненный цикл работы с браком: создание, утверждение, забор на склад и
удаление.

## Зависимости и сервисы

- `App\Http\Requests\SaveDefectMaterialRequest` - класс валидации сохранения
  брака
- `App\Models\Material` - модель материала
- `App\Models\Order` - модель заказа
- `App\Models\Supplier` - модель поставщика
- `App\Models\User` - модель пользователя
- `App\Services\DefectMaterialService` - сервис обработки брака
- `App\Services\InventoryService` - сервис инвентаризации
- `App\Services\OrderService` - сервис работы с заказами

## Список методов

### index(Request $request)

- **Описание:** Отображение списка всех бракованных материалов с фильтрацией
- **URL:** `/defect_materials`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `defect_materials.index`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `materials` (Collection) - количество материалов на складе брака через
      `InventoryService::materialsQuantityBy('defect_warehouse')`
    - `orders` (LengthAwarePaginator) - отфильтрованные заказы с пагинацией (10
      на страницу)
    - `users` (Collection) - пользователи с ролями 1 и 4, исключая тестовых
- **Функциональность:**
    - Сохранение полного URL в сессии для возврата
    - Фильтрация через `OrderService::getFiltered($request)`

### create(Request $request)

- **Описание:** Форма создания новой записи о браке или остатках
- **URL:** `/defect_materials/create`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `defect_materials.create`
- **Данные:**
    - `title` (string) - динамический заголовок в зависимости от
      `type_movement_id` (4 - брак, иначе - остаток)
    - `materials` (Collection) - список всех материалов
    - `suppliers` (Collection) - список всех поставщиков
- **Параметры запроса:**
    - `type_movement_id` (int) - определяет тип создаваемой записи

### store(SaveDefectMaterialRequest $request)

- **Описание:** Сохранение новой записи о браке/остатках
- **URL:** `/defect_materials`
- **Метод:** POST
- **Параметры:** Валидируются через `SaveDefectMaterialRequest`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке сервиса: редирект с flash сообщением `error` - 'Внутренняя
      ошибка'
- **Успешный редирект:** `route('defect_materials.index')`
- **Flash сообщение:** `success` - 'Брак добавлен'
- **Делегирование:** Бизнес-логика вынесена в `DefectMaterialService::store()`

### approve_reject(Order $order)

- **Описание:** Форма одобрения или отклонения брака
- **URL:** `/defect_materials/{order}/approve_reject`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `defect_materials.approve_reject`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `order` (Order) - заказ для одобрения/отклонения
    - `materials` (Collection) - список всех материалов

### pick_up(Order $order)

- **Описание:** Форма забора брака на склад
- **URL:** `/defect_materials/{order}/pick_up`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `defect_materials.pick_up`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `order` (Order) - заказ для забора
    - `materials` (Collection) - список всех материалов

### save(Order $order, Request $request)

- **Описание:** Сохранение решения по браку (одобрение/отклонение)
- **URL:** `/defect_materials/{order}/save`
- **Метод:** POST
- **Параметры:**
    - `status` (int) - новый статус заказа
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При неверном значении: редирект с flash сообщением `error` - 'Неверное
      значение'
- **Успешный редирект:** Возврат на сохраненный URL из сессии или на главную
- **Flash сообщение:** Динамическое сообщение о результате операции
- **Действия:**
    1. Вызов `DefectMaterialService::save($request, $order)`
    2. Обновление статуса заказа
    3. Возврат на предыдущую страницу

### delete(Order $order)

- **Описание:** Удаление записи о браке
- **URL:** `/defect_materials/{order}/delete`
- **Метод:** GET/DELETE
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке удаления: редирект с flash сообщением `error`
- **Успешный редирект:** `route('defect_materials.index')`
- **Flash сообщение:** `success` - сообщение об успешном удалении
- **Делегирование:** Бизнес-логика вынесена в `DefectMaterialService::delete()`

## Работа с моделями данных

- **Order:** Основная модель для хранения информации о браке
- **Material:** Справочник материалов
- **Supplier:** Справочник поставщиков
- **User:** Справочник пользователей с фильтрацией по ролям

## Типы движения

- `type_movement = 4` - передача брака на склад

## Роли пользователей

- `role_id = 1` - Администратор
- `role_id = 4` - Кладовщик

## Валидация

- Используется `SaveDefectMaterialRequest` для валидации входных данных

## Права доступа

- Контроллер не использует Gate или middleware для проверки прав доступа
- Предполагается использование глобальных middleware для аутентификации

## Особенности реализации

- Вся бизнес-логика вынесена в сервис `DefectMaterialService`
- Использование сессии для сохранения URL возврата после фильтрации
- Динамические заголовки в зависимости от типа движения
- Интеграция с сервисом инвентаризации для отображения остатков
- Фильтрация пользователей по ролям и исключению тестовых аккаунтов
- Сложный жизненный цикл обработки брака с несколькими статусами
