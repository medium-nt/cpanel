# MovementDefectMaterialToSupplierController

**Путь:** `app/Http/Controllers/MovementDefectMaterialToSupplierController.php`

## Общее назначение

Контроллер для управления возвратом бракованных материалов поставщикам.
Обеспечивает создание и отображение записей о возврате брака.

## Зависимости и сервисы

- `App\Http\Requests\StoreDefectMaterialToSupplierRequest` - класс валидации
  запроса
- `App\Models\Material` - модель материала
- `App\Models\Order` - модель заказа
- `App\Models\Supplier` - модель поставщика
- `App\Services\MovementDefectMaterialToSupplierService` - сервис обработки
  возврата брака

## Список методов

### index()

- **Описание:** Отображение списка всех возвратов брака поставщикам
- **URL:** `/movements_defect_to_supplier`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_defect_to_supplier.index`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `orders` (LengthAwarePaginator) - коллекция заказов с типом движения 5 (
      возврат брака) с пагинацией (10 на страницу)
- **Фильтрация:**
    - `where('type_movement', 5)` - только заказы типа возврат брака
    - `latest()` - сортировка по убыванию даты создания

### create()

- **Описание:** Форма создания нового возврата брака поставщику
- **URL:** `/movements_defect_to_supplier/create`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `movements_defect_to_supplier.create`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `materials` (Collection) - список всех материалов
    - `suppliers` (Collection) - список всех поставщиков

### store(StoreDefectMaterialToSupplierRequest $request)

- **Описание:** Сохранение нового возврата брака в системе
- **URL:** `/movements_defect_to_supplier`
- **Метод:** POST
- **Параметры:** Валидируются через `StoreDefectMaterialToSupplierRequest`
- **Возвращает:** Результат выполнения
  `MovementDefectMaterialToSupplierService::store($request)`
- **Делегирование:** Вся бизнес-логика перенесена в сервисный слой

## Закомментированные методы

Следующие методы закомментированы и не используются:

- `edit(Order $order)` - форма редактирования
- `update(UpdateMovementMaterialFromSupplierRequest $request, Order $order)` -
  обновление
- `destroy(MovementMaterial $movementMaterial)` - удаление

## Работа с моделями данных

- **Order:** Основная модель для хранения информации о возвратах брака
- **Material:** Справочник материалов
- **Supplier:** Справочник поставщиков

## Типы движения

- `type_movement = 5` - возврат брака поставщику

## Валидация

Используется отдельный класс валидации `StoreDefectMaterialToSupplierRequest`
для проверки входных данных.

## Права доступа

- Контроллер не использует Gate или middleware для проверки прав доступа
- Предполагается использование глобальных middleware для аутентификации

## Особенности реализации

- Вся бизнес-логика вынесена в сервис `MovementDefectMaterialToSupplierService`
- Контроллер выполняет только роль посредника между HTTP-слоем и сервисным слоем
- Методы редактирования и удаления закомментированы, что говорит о неизменности
  возвратов брака после создания
