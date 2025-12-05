# MovementMaterialFromSupplierController

**Путь:** `app/Http/Controllers/MovementMaterialFromSupplierController.php`

## Общее назначение

Контроллер для управления поступлением материалов от поставщиков на склад.
Обеспечивает полный CRUD-функционал для работы с поставками.

## Зависимости и сервисы

- `App\Http\Requests\StoreMovementMaterialFromSupplierRequest` - валидация
  создания поставки
- `App\Http\Requests\UpdateMovementMaterialFromSupplierRequest` - валидация
  обновления поставки
- `App\Models\Material` - модель материала
- `App\Models\MovementMaterial` - модель движения материалов
- `App\Models\Order` - модель заказа
- `App\Models\Supplier` - модель поставщика
- `App\Services\MovementMaterialFromSupplierService` - сервис обработки поставок

## Список методов

### index()

- **Описание:** Отображение списка всех поступлений материалов от поставщиков
- **URL:** `/movements_from_supplier`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_from_supplier.index`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `orders` (LengthAwarePaginator) - коллекция заказов с пагинацией (10 на
      страницу)
- **Фильтрация:**
    - `where('type_movement', 1)` - только заказы типа поступление от поставщика
    - `latest()` - сортировка по убыванию даты создания

### create()

- **Описание:** Форма создания нового поступления материалов
- **URL:** `/movements_from_supplier/create`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_from_supplier.create`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `materials` (Collection) - список всех материалов
    - `suppliers` (Collection) - список всех поставщиков

### store(StoreMovementMaterialFromSupplierRequest $request)

- **Описание:** Сохранение нового поступления материалов в системе
- **URL:** `/movements_from_supplier`
- **Метод:** POST
- **Параметры:** Валидируются через `StoreMovementMaterialFromSupplierRequest`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке сервиса: `back()->withErrors(['error' => 'Внутренняя ошибка'])`
- **Успешный редирект:** `route('movements_from_supplier.index')`
- **Flash сообщение:** `success` - 'Поступление добавлено'
- **Делегирование:** Бизнес-логика вынесена в
  `MovementMaterialFromSupplierService::store()`

### show(MovementMaterial $movementMaterial)

- **Описание:** Отображение информации о конкретном движении материала
- **URL:** `/movements_from_supplier/{movementMaterial}`
- **Метод:** GET
- **Статус:** Не реализован

### edit(Order $order)

- **Описание:** Форма редактирования поставки
- **URL:** `/movements_from_supplier/{order}/edit`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_from_supplier.edit`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `order` (Order) - редактируемый заказ
    - `materials` (Collection) - список всех материалов

### update(UpdateMovementMaterialFromSupplierRequest $request, Order $order)

- **Описание:** Обновление данных о поставке
- **URL:** `/movements_from_supplier/{order}`
- **Метод:** PUT/PATCH
- **Параметры:** Валидируются через `UpdateMovementMaterialFromSupplierRequest`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Обработка ошибок:**
    - При ошибке сервиса: `back()->withErrors(['error' => 'Внутренняя ошибка'])`
- **Успешный редирект:** `route('movements_from_supplier.index')`
- **Flash сообщение:** `success` - 'Поступление добавлено'
- **Делегирование:** Бизнес-логика вынесена в
  `MovementMaterialFromSupplierService::update()`

### destroy(MovementMaterial $movementMaterial)

- **Описание:** Удаление движения материала
- **URL:** `/movements_from_supplier/{movementMaterial}`
- **Метод:** DELETE
- **Статус:** Не реализован

## Работа с моделями данных

- **Order:** Основная модель для хранения информации о поставках
- **Material:** Справочник материалов
- **Supplier:** Справочник поставщиков
- **MovementMaterial:** Модель для отслеживания движений материалов

## Типы движения

- `type_movement = 1` - поступление материала от поставщика

## Валидация

Используются отдельные классы валидации:

- `StoreMovementMaterialFromSupplierRequest` - для создания
- `UpdateMovementMaterialFromSupplierRequest` - для обновления

## Права доступа

- Контроллер не использует Gate или middleware для проверки прав доступа
- Предполагается использование глобальных middleware для аутентификации

## Особенности реализации

- Вся бизнес-логика вынесена в сервис `MovementMaterialFromSupplierService`
- Сервис возвращает boolean для определения успешности операции
- Единый подход к обработке ошибок через flash сообщения
- Контроллер выполняет только роль посредника между HTTP-слоем и сервисным слоем
