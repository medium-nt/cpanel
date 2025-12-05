# InventoryController

**Путь:** `app/Http/Controllers/InventoryController.php`

**Описание:** Контроллер для управления инвентаризацией и учета материалов на
складе и производстве

**Зависимости:**

- `App\Http\Requests\StoreInventoryRequest` - Form Request для валидации
- `App\Models\InventoryCheck` - модель инвентаризации
- `App\Models\Shelf` - модель полок
- `App\Services\InventoryService` - сервис инвентаризации

---

## Методы контроллера

### byWarehouse()

- **Описание:** Отображение материалов на складе
- **Возвращает:** `Illuminate\View\View`
- **Использует:** `InventoryService::materialsQuantityBy('warehouse')`
- **Данные:**
    - `title` (string) - "Материал на складе"
    - `materials` (Collection) - материалы на складе
- **View:** `inventory.warehouse`

### byWorkshop()

- **Описание:** Отображение материалов на производстве
- **Возвращает:** `Illuminate\View\View`
- **Использует:** `InventoryService::materialsQuantityBy('workhouse')`
- **Данные:**
    - `title` (string) - "Материал на производстве"
    - `materials` (Collection) - материалы на производстве
- **View:** `inventory.workshop`

### inventoryChecks()

- **Описание:** Отображение списка инвентаризаций
- **Возвращает:** `Illuminate\View\View`
- **Параметры запроса:**
    - `status` (string, optional) - статус инвентаризации (по умолчанию '
      in_progress')
- **Данные:**
    - `title` (string) - "Инвентаризации"
    - `inventories` (LengthAwarePaginator) - пагинированный список
      инвентаризаций
- **View:** `inventory.index`

### show(InventoryCheck $inventory)

- **Описание:** Просмотр деталей инвентаризации
- **Параметры:**
    - `$inventory` (InventoryCheck) - модель инвентаризации
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Инвентаризация №{id} ({статус})"
    - `inventory` (InventoryCheck) - модель инвентаризации
    - `items` (Collection) - элементы инвентаризации
- **Статусы:**
    - `in_progress` - "В процессе"
    - `closed` - "Закрыта"
- **View:** `inventory.show`

### create()

- **Описание:** Показ формы создания новой инвентаризации
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Новая инвентаризация"
    - `shelfs` (Collection) - все полки
- **View:** `inventory.create`

### store(StoreInventoryRequest $request, InventoryService $inventoryService)

- **Описание:** Создание новой инвентаризации
- **Параметры:**
    - `$request` (StoreInventoryRequest) - валидированные данные
    - `$inventoryService` (InventoryService) - сервис инвентаризации
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:**
    - Вызывает метод `createInventory()` сервиса
    - При ошибке - возвращает с ошибкой
    - При успехе - редирект с сообщением
- **Редирект:** `route('inventory.inventory_checks')`

### destroy(InventoryCheck $inventory)

- **Описание:** Удаление инвентаризации
- **Параметры:**
    - `$inventory` (InventoryCheck) - модель инвентаризации
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:** Простое удаление модели
- **Редирект:** `route('inventory.inventory_checks')`

---

## Особенности реализации

1. **Использование сервисного слоя:** Основная бизнес-логика вынесена в
   `InventoryService`
2. **Form Request валидация:** Используется класс `StoreInventoryRequest` для
   валидации входных данных
3. **Route Model Binding:** Автоматическая подгрузка моделей из параметров
   роутов
4. **Фильтрация по статусу:** Метод `inventoryChecks()` поддерживает фильтрацию
   по query параметру
5. **Пагинация:** Используется пагинация по 10 записей на странице

---

## Роуты

- `GET /inventory/warehouse` - `byWarehouse` - материалы на складе
- `GET /inventory/workshop` - `byWorkshop` - материалы на производстве
- `GET /inventory/inventory_checks` - `inventoryChecks` - список инвентаризаций
- `GET /inventory/{inventory}` - `show` - детали инвентаризации
- `GET /inventory/create` - `create` - форма создания
- `POST /inventory` - `store` - сохранение инвентаризации
- `DELETE /inventory/{inventory}` - `destroy` - удаление инвентаризации

---

## Зависимости сервисов

### InventoryService

- `materialsQuantityBy(string $type)` - получение количества материалов по типу
- `createInventory(Request $request)` - создание новой инвентаризации
