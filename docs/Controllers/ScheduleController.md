# ScheduleController

**Путь:** `app/Http/Controllers/ScheduleController.php`

**Описание:** Контроллер для управления рабочим расписанием сотрудников

**Зависимости:**

- `App\Models\Schedule` - модель расписания
- `Illuminate\Http\Request` - HTTP запросы
- `Illuminate\Support\Facades\Validator` - валидатор

---

## Методы контроллера

### changeDate(Request $request)

- **Описание:** Добавление или удаление рабочей даты в расписании сотрудника
- **Параметры:** `$request` - HTTP запрос
- **Возвращает:** `Illuminate\Http\JsonResponse`
- **Тип запроса:** AJAX (только для AJAX запросов)
- **Валидация:**
  ```php
  [
      'user_id' => ['required', 'exists:users,id'],
      'date' => ['required', 'date']
  ]
  ```
- **Логика работы:**
    1. Проверяет, что запрос является AJAX
    2. Валидирует входные данные
    3. Ищет или создает запись в расписании по `user_id` и `date`
    4. Если запись была создана недавно - создает новую запись
    5. Если запись уже существовала - удаляет ее (toggle логика)
- **Возвращаемые данные:**
    - **Успех:**
      ```json
      {
          "message": "Расписание успешно обновлено",
          "deleted": false, // или true если удалено
          "id": 123 // ID записи
      }
      ```
    - **Ошибка:**
      ```json
      {
          "message": "Возникла проблема при сохранении расписания"
      }
      ```

---

## Особенности реализации

1. **Toggle логика:** Один метод отвечает и за добавление, и за удаление записей
2. **AJAX-only:** Метод работает только с AJAX запросами, иначе возвращает 404
3. **FirstOrCreate:** Используется Eloquent метод для атомарной операции
4. **wasRecentlyCreated:** Свойство модели для определения статуса операции

---

## Модель Schedule (используется в контроллере)

### Поля:

- `user_id` (int) - ID пользователя
- `date` (date) - дата работы
- `created_at` (timestamp) - время создания
- `updated_at` (timestamp) - время обновления

### Отношения:

- `user` - принадлежность к пользователю

---

## Роуты

- `POST /schedule/change_date` - `changeDate` - изменение расписания

---

## Пример использования

### JavaScript пример для фронтенда:

```javascript
function toggleSchedule(userId, date) {
    fetch('/schedule/change_date', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            user_id: userId,
            date: date
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.deleted) {
            // Дата удалена из расписания
            console.log('Дата удалена');
        } else {
            // Дата добавлена в расписание
            console.log('Дата добавлена, ID:', data.id);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
    });
}
```

---

## Особенности frontend интеграции

1. **Календарь:** Обычно используется с интерактивным календарем
2. **Визуальная обратная связь:** Изменение цвета даты при добавлении/удалении
3. **Batch операции:** Можно вызывать несколько раз для разных дат
4. **Валидация на клиенте:** Рекомендуется проверять формат даты перед отправкой
