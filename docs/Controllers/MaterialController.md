# MaterialController

**Путь:** `app/Http/Controllers/MaterialController.php`

## Общее назначение

Контроллер для управления материалами в системе. Обеспечивает CRUD-операции для
работы с материалами: создание, чтение, обновление и удаление.

## Зависимости и сервисы

- `App\Models\Material` - модель материала
- `App\Models\TypeMaterial` - модель типа материала
- `Illuminate\Http\Request` - HTTP запросы
- `Illuminate\View\View` - отрисовка представлений

## Список методов

### index()

- **Описание:** Отображение списка всех материалов с пагинацией
- **URL:** `/materials`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `materials.index`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `materials` (LengthAwarePaginator) - коллекция материалов с пагинацией (10
      на странице)

### create()

- **Описание:** Форма создания нового материала
- **URL:** `/materials/create`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `materials.create`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `typesMaterial` (Collection) - список всех типов материалов

### store(Request $request)

- **Описание:** Сохранение нового материала в базе данных
- **URL:** `/materials`
- **Метод:** POST
- **Параметры:**
    - `title` (string, required) - название материала (уникальное, 2-255
      символов)
    - `type_id` (int, required) - ID типа материала (должен существовать в
      type_materials)
    - `unit` (string, required) - единица измерения (1-10 символов)
- **Валидация:**
  ```php
  $rules = [
      'title' => 'required|string|unique:materials,title|min:2|max:255',
      'type_id' => 'required|integer|exists:type_materials,id',
      'unit' => 'required|string|min:1|max:10',
  ];
  ```
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('materials.index')`
- **Flash сообщение:** `success` - 'Материал добавлен'

### show(Material $material)

- **Описание:** Отображение информации о конкретном материале
- **URL:** `/materials/{material}`
- **Метод:** GET
- **Статус:** Не реализован

### edit(Material $material)

- **Описание:** Форма редактирования материала
- **URL:** `/materials/{material}/edit`
- **Метод:** GET
- **Возвращает:** `Illuminate\View\View`
- **Представление:** `materials.edit`
- **Данные:**
    - `title` (string) - заголовок страницы
    - `material` (Material) - редактируемый материал
    - `typesMaterial` (Collection) - список всех типов материалов

### update(Request $request, Material $material)

- **Описание:** Обновление данных материала
- **URL:** `/materials/{material}`
- **Метод:** PUT/PATCH
- **Параметры:**
    - `title` (string, required) - название материала (2-255 символов)
    - `type_id` (int, required) - ID типа материала
    - `unit` (string, required) - единица измерения (1-10 символов)
- **Валидация:**
  ```php
  $rules = [
      'title' => 'required|string|min:2|max:255',
      'type_id' => 'required|integer|exists:type_materials,id',
      'unit' => 'required|string|min:1|max:10',
  ];
  ```
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('materials.index')`
- **Flash сообщение:** `success` - 'Изменения сохранены'

### destroy(Material $material)

- **Описание:** Удаление материала из системы
- **URL:** `/materials/{material}`
- **Метод:** DELETE
- **Проверки:** Проверяет наличие связанных записей в `movementMaterials`
- **Бизнес-правила:**
    - Нельзя удалить материал, если он используется в движениях материалов
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('materials.index')`
- **Flash сообщения:**
    - `error` - 'Невозможно удалить материал, так как он используется в системе'
    - `success` - 'Материал удален'

## Работа с моделями данных

- **Material:** Основная модель для работы с материалами
- **TypeMaterial:** Справочник типов материалов
- **MovementMaterial:** Проверка связанных записей при удалении

## Права доступа

- Контроллер не использует Gate или middleware для проверки прав доступа
- Предполагается использование глобальных middleware для аутентификации
