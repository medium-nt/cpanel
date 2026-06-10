# ShelfController

**Путь:** `app/Http/Controllers/ShelfController.php`

**Описание:** Контроллер для управления полками на складе

**Зависимости:**

- `App\Models\Shelf` - модель полки
- `Illuminate\Http\Request` - HTTP запросы

---

## Методы контроллера

### index()

- **Описание:** Отображение списка всех полок
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Полки на складе"
    - `shelves` (LengthAwarePaginator) - пагинированный список полок (20 на
      страницу)
- **View:** `shelves.index`

### create()

- **Описание:** Показ формы создания новой полки
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Добавить полку"
- **View:** `shelves.create`

### store(Request $request)

- **Описание:** Сохранение новой полки
- **Параметры:**
    - `$request` (Request) - HTTP запрос с данными полки
- **Валидация:**
  ```php
  [
      'title' => 'required|string|min:2|max:255'
  ]
  ```
- **Сообщения валидации:**
    - `title.required` - "Поле обязательно для заполнения"
    - `title.string` - "Поле должно быть строкой"
    - `title.min` - "Поле должно быть не менее :min символов"
    - `title.max` - "Поле должно быть не более :max символов"
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('shelves.index')` с сообщением об успехе

### edit(Shelf $shelf)

- **Описание:** Показ формы редактирования полки
- **Параметры:**
    - `$shelf` (Shelf) - модель полки (Route Model Binding)
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Редактирование полки"
    - `shelf` (Shelf) - модель полки
- **View:** `shelves.edit`

### update(Request $request, Shelf $shelf)

- **Описание:** Обновление данных полки
- **Параметры:**
    - `$request` (Request) - HTTP запрос с данными
    - `$shelf` (Shelf) - модель полки (Route Model Binding)
- **Валидация:** Аналогична методу `store`
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('shelves.index')` с сообщением об успехе

### destroy(Shelf $shelf)

- **Описание:** Удаление полки
- **Параметры:**
    - `$shelf` (Shelf) - модель полки (Route Model Binding)
- **Проверки:**
    - Проверяет наличие связанных элементов заказа (`orderItems`)
    - Если есть связанные элементы - удаляет нельзя
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:** `route('shelves.index')` с сообщением об успехе или ошибке

---

## Особенности реализации

1. **Пагинация:** В методе `index` используется пагинация по 20 записей на
   страницу
2. **Проверка связей:** При удалении полки проверяется наличие связанных
   элементов заказа
3. **Валидация:** Используется встроенная валидация Laravel с кастомными
   сообщениями
4. **Route Model Binding:** Используется для автоматического поиска моделей по
   ID из URL
5. **Flash сообщения:** Используются flash сообщения для информирования
   пользователя об операциях

---

## Роуты

- `GET /shelves` - `index` - список полок
- `GET /shelves/create` - `create` - форма создания
- `POST /shelves` - `store` - сохранение
- `GET /shelves/{shelf}/edit` - `edit` - форма редактирования
- `PUT/PATCH /shelves/{shelf}` - `update` - обновление
- `DELETE /shelves/{shelf}` - `destroy` - удаление
