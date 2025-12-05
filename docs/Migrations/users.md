# Таблицы пользователей и управления доступом

## users - Пользователи системы

**Файл миграции:** `0001_01_01_000000_create_users_table.php`

### Структура таблицы:

| Колонка                 | Тип           | Описание                    | Индекс/Ключ            |
|-------------------------|---------------|-----------------------------|------------------------|
| id                      | bigint        | ID пользователя             | PRIMARY                |
| name                    | string        | Имя пользователя            |                        |
| email                   | string(255)   | Email пользователя          | UNIQUE                 |
| phone                   | string        | Телефон пользователя        |                        |
| email_verified_at       | timestamp     | Дата верификации email      |                        |
| password                | string(255)   | Хэш пароля                  |                        |
| role_id                 | bigint        | ID роли                     | FOREIGN KEY (roles.id) |
| is_cutter               | boolean       | Является ли резчиком        | DEFAULT false          |
| salary_rate             | decimal(10,2) | Ставка зарплаты             | DEFAULT 0              |
| orders_priority         | string        | Приоритет заказов           | DEFAULT 'all'          |
| shift_is_open           | boolean       | Открыта ли смена            | DEFAULT false          |
| start_work_shift        | time          | Начало рабочей смены        | DEFAULT '00:00:00'     |
| closed_work_shift       | time          | Конец рабочей смены         | DEFAULT '00:00:00'     |
| duration_work_shift     | time          | Длительность смены          | DEFAULT '00:00:00'     |
| max_late_minutes        | integer       | Макс. опоздание (мин)       | DEFAULT 0              |
| actual_start_work_shift | time          | Фактич. начало смены        | DEFAULT '00:00:00'     |
| remember_token          | string(100)   | Токен remember me           |                        |
| avatar                  | string        | Путь к аватару              | NULLABLE               |
| tg_id                   | string        | Telegram ID                 | NULLABLE               |
| is_show_finance         | boolean       | Показывать финансы          | DEFAULT false          |
| created_at              | timestamp     | Дата создания               |                        |
| updated_at              | timestamp     | Дата обновления             |                        |
| deleted_at              | timestamp     | Дата удаления (soft delete) |                        |

### Связи:

- `role_id` → `roles.id` (многие к одному)
- Связь с `schedules` через `user_id` (один ко многим)
- Связь с `transactions` (через отправителя/получателя)
- Связь с `orders` (исполнитель)

## roles - Роли пользователей

**Файл миграции:** `2025_03_10_061620_create_roles_table.php`

### Структура таблицы:

| Колонка    | Тип         | Описание        | Индекс/Ключ |
|------------|-------------|-----------------|-------------|
| id         | bigint      | ID роли         | PRIMARY     |
| name       | string(255) | Название роли   | UNIQUE      |
| created_at | timestamp   | Дата создания   |             |
| updated_at | timestamp   | Дата обновления |             |

### Связи:

- Связь с `users` через `role_id` (один ко многим)

## schedules - Графики работы сотрудников

**Файл миграции:** `2025_04_25_115925_create_schedules_table.php`

### Структура таблицы:

| Колонка           | Тип       | Описание             | Индекс/Ключ            |
|-------------------|-----------|----------------------|------------------------|
| id                | bigint    | ID записи            | PRIMARY                |
| user_id           | bigint    | ID пользователя      | FOREIGN KEY (users.id) |
| date              | date      | Дата работы          |                        |
| shift_opened_time | time      | Время открытия смены | DEFAULT '00:00:00'     |
| shift_closed_time | time      | Время закрытия смены | DEFAULT '00:00:00'     |
| created_at        | timestamp | Дата создания        |                        |
| updated_at        | timestamp | Дата обновления      |                        |

### Связи:

- `user_id` → `users.id` (многие к одному, CASCADE DELETE)

## password_reset_tokens - Токены сброса пароля

**Файл миграции:** `0001_01_01_000000_create_users_table.php`

### Структура таблицы:

| Колонка    | Тип         | Описание           | Индекс/Ключ |
|------------|-------------|--------------------|-------------|
| email      | string(255) | Email пользователя | PRIMARY     |
| token      | string(255) | Токен сброса       |             |
| created_at | timestamp   | Дата создания      |             |

## sessions - Сессии пользователей

**Файл миграции:** `0001_01_01_000000_create_users_table.php`

### Структура таблицы:

| Колонка       | Тип         | Описание                   | Индекс/Ключ |
|---------------|-------------|----------------------------|-------------|
| id            | string(255) | ID сессии                  | PRIMARY     |
| user_id       | bigint      | ID пользователя            | INDEX       |
| ip_address    | string(45)  | IP адрес                   |             |
| user_agent    | text        | User Agent браузера        |             |
| payload       | longtext    | Данные сессии              |             |
| last_activity | integer     | Время последней активности | INDEX       |

### Особенности структуры:

1. **Пользователи** имеют расширенную информацию для управления производственным
   процессом:
    - Отслеживание рабочих смен и времени
    - Роли и специализации (резчик)
    - Интеграция с Telegram
    - Настройки отображения финансов

2. **Графики работы** позволяют отслеживать:
    - Фактическое время работы сотрудников
    - Опоздания и переработки

3. **Soft delete** для пользователей позволяет сохранять историю
