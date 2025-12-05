# Таблицы финансов и мотивации

## transactions - Финансовые транзакции

**Файл миграции:** `2025_04_16_031726_create_transactions_table.php`

### Структура таблицы:

| Колонка                   | Тип           | Описание            | Индекс/Ключ                                        |
|---------------------------|---------------|---------------------|----------------------------------------------------|
| id                        | bigint        | ID транзакции       | PRIMARY                                            |
| user_id                   | bigint        | ID пользователя     | FOREIGN KEY (users.id), NULLABLE                   |
| title                     | string(255)   | Название транзакции |                                                    |
| marketplace_order_item_id | bigint        | ID элемента заказа  | FOREIGN KEY (marketplace_order_items.id), NULLABLE |
| accrual_for_date          | date          | Дата начисления     | NULLABLE                                           |
| amount                    | decimal(10,0) | Сумма               |                                                    |
| status                    | integer       | Статус              |                                                    |
| transaction_type          | enum          | Тип (out/in)        | NULLABLE                                           |
| paid_at                   | timestamp     | Дата оплаты         | NULLABLE                                           |
| is_bonus                  | boolean       | Является бонусом    | DEFAULT false                                      |
| created_at                | timestamp     | Дата создания       |                                                    |
| updated_at                | timestamp     | Дата обновления     |                                                    |

### Связи:

- `user_id` → `users.id` (многие к одному, RESTRICT)
- `marketplace_order_item_id` → `marketplace_order_items.id` (многие к одному,
  RESTRICT)

## rates - Ставки оплаты

**Файл миграции:** `2025_08_26_062330_create_rates_table.php`

### Структура таблицы:

| Колонка         | Тип       | Описание         | Индекс/Ключ                         |
|-----------------|-----------|------------------|-------------------------------------|
| id              | bigint    | ID ставки        | PRIMARY                             |
| user_id         | bigint    | ID пользователя  | FOREIGN KEY (users.id), CASCADE     |
| material_id     | bigint    | ID материала     | FOREIGN KEY (materials.id), CASCADE |
| rate            | integer   | Общая ставка     | DEFAULT 0                           |
| not_cutter_rate | integer   | Ставка без резки | DEFAULT 0                           |
| cutter_rate     | integer   | Ставка с резкой  | DEFAULT 0                           |
| created_at      | timestamp | Дата создания    |                                     |
| updated_at      | timestamp | Дата обновления  |                                     |

### Связи:

- `user_id` → `users.id` (многие к одному, CASCADE DELETE)
- `material_id` → `materials.id` (многие к одному, CASCADE DELETE)

## motivations - Мотивационные бонусы

**Файл миграции:** `2025_08_16_054550_create_motivations_table.php`

### Структура таблицы:

| Колонка          | Тип       | Описание        | Индекс/Ключ                     |
|------------------|-----------|-----------------|---------------------------------|
| id               | bigint    | ID мотивации    | PRIMARY                         |
| user_id          | bigint    | ID пользователя | FOREIGN KEY (users.id), CASCADE |
| from             | integer   | От (количество) | DEFAULT 0                       |
| to               | integer   | До (количество) | DEFAULT 0                       |
| bonus            | integer   | Общий бонус     | DEFAULT 0                       |
| not_cutter_bonus | integer   | Бонус без резки | DEFAULT 0                       |
| cutter_bonus     | integer   | Бонус с резкой  | DEFAULT 0                       |
| created_at       | timestamp | Дата создания   |                                 |
| updated_at       | timestamp | Дата обновления |                                 |

### Связи:

- `user_id` → `users.id` (многие к одному, CASCADE DELETE)

### Особенности структуры:

1. **Транзакции** отслеживают все финансовые операции:
    - Начисления сотрудникам
    - Связь с конкретными элементами заказов
    - Даты начисления и оплаты
    - Разделение на обычные платежи и бонусы

2. **Ставки оплаты** позволяют настраивать:
    - Индивидуальные ставки для сотрудников
    - Различные ставки в зависимости от материала
    - Дифференциация ставок для операций с резкой и без

3. **Мотивационные бонусы** предоставляют:
    - Гибкую систему бонусов в зависимости от объема
    - Различные бонусы для разных типов работ
    - Пороговые значения для начисления бонусов

Все финансовые таблицы обеспечивают полный учет и мотивацию сотрудников с учетом
специфики производственного процесса.
