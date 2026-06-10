# SupplierController

**Путь:** `app/Http/Controllers/SupplierController.php`

**Описание:** Контроллер для управления поставщиками

**Зависимости:**

- `App\Models\Supplier` - модель поставщика
- `Illuminate\Http\Request` - HTTP запросы

---

## Методы контроллера

### index()

- **Описание:** Отображение списка всех поставщиков
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Поставщики"
    - `suppliers` (LengthAwarePaginator) - пагинированный список поставщиков (10
      на страницу)
- **View:** `suppliers.index`

### create()

- **Описание:** Показ формы создания нового поставщика
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Добавить поставщика"
- **View:** `suppliers.create`

### store(Request $request)

- **Описание:** Сохранение нового поставщика
- **Параметры:**
    - `$request` (Request) - HTTP запрос с данными поставщика
- **Валидация:**
  ```php
  [
      'title' => 'required|string|min:2|max:255',
      'phone' => 'required|string|min:2|max:255',
      'address' => 'required|string|min:2|max:255',
      'comment' => 'nullable|string|min:2|max:255'
  ]
  ```
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('suppliers.index')` с сообщением об успехе
- **Особенность:** Использует метод `create()` модели для массового присвоения

### edit(Supplier $supplier)

- **Описание:** Показ формы редактирования поставщика
- **Параметры:**
    - `$supplier` (Supplier) - модель поставщика (Route Model Binding)
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Изменить поставщика"
    - `supplier` (Supplier) - модель поставщика
- **View:** `suppliers.edit`

### update(Request $request, Supplier $supplier)

- **Описание:** Обновление данных поставщика
- **Параметры:**
    - `$request` (Request) - HTTP запрос с данными
    - `$supplier` (Supplier) - модель поставщика (Route Model Binding)
- **Валидация:** Аналогична методу `store`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('suppliers.index')` с сообщением об успехе
- **Особенность:** Использует метод `update()` модели для массового обновления

### destroy(Supplier $supplier)

- **Описание:** Удаление поставщика
- **Параметры:**
    - `$supplier` (Supplier) - модель поставщика (Route Model Binding)
- **Проверки:**
    - Проверяет наличие связанных заказов (`orders`)
    - Если есть связанные заказы - удаляет нельзя
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('suppliers.index')` с сообщением об успехе или ошибке

---

## Особенности реализации

1. **Пагинация:** В методе `index` используется пагинация по 10 записей на
   страницу
2. **Проверка целостности данных:** При удалении поставщика проверяется наличие
   связанных заказов
3. **Массовое присвоение:** Используются методы `create()` и `update()` моделей
   для безопасной работы с данными
4. **Route Model Binding:** Автоматическая подгрузка моделей по параметру
   маршрута
5. **Nullable поля:** Поле `comment` может быть пустым (nullable)

---

## Роуты

- `GET /suppliers` - `index` - список поставщиков
- `GET /suppliers/create` - `create` - форма создания
- `POST /suppliers` - `store` - сохранение
- `GET /suppliers/{supplier}/edit` - `edit` - форма редактирования
- `PUT/PATCH /suppliers/{supplier}` - `update` - обновление
- `DELETE /suppliers/{supplier}` - `destroy` - удаление

---

## Права доступа

Контроллер не имеет явной проверки прав, предполагается использование middleware
на уровне роутов для ограничения доступа к управлению поставщиками.
