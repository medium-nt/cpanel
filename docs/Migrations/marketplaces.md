# Таблицы маркетплейсов и заказов

## marketplace_orders - Заказы с маркетплейсов

**Файл миграции:** `2025_04_02_130800_create_marketplace_orders_table.php`

### Структура таблицы:

| Колонка            | Тип         | Описание                  | Индекс/Ключ                                     |
|--------------------|-------------|---------------------------|-------------------------------------------------|
| id                 | bigint      | ID заказа                 | PRIMARY                                         |
| order_id           | string(255) | ID заказа на маркетплейсе | UNIQUE                                          |
| marketplace_id     | integer     | ID маркетплейса           |                                                 |
| fulfillment_type   | enum        | Тип выполнения (FBS/FBO)  | DEFAULT 'FBO'                                   |
| part_b             | string      | Часть B                   | NULLABLE                                        |
| barcode            | string      | Штрих-код                 | NULLABLE                                        |
| status             | string(255) | Статус заказа             |                                                 |
| is_printed         | boolean     | Напечатан ли заказ        | DEFAULT false                                   |
| supply_id          | bigint      | ID поставки               | FOREIGN KEY (marketplace_supplies.id), NULLABLE |
| marketplace_status | string      | Статус на маркетплейсе    | NULLABLE                                        |
| completed_at       | timestamp   | Дата завершения           | NULLABLE                                        |
| returned_at        | timestamp   | Дата возврата             | NULLABLE                                        |
| created_at         | timestamp   | Дата создания             |                                                 |
| updated_at         | timestamp   | Дата обновления           |                                                 |

### Связи:

- `supply_id` → `marketplace_supplies.id` (многие к одному, SET NULL)

## marketplace_order_items - Элементы заказов

**Файл миграции:** `2025_04_06_164938_create_marketplace_order_items_table.php`

### Структура таблицы:

| Колонка              | Тип           | Описание              | Индекс/Ключ                         |
|----------------------|---------------|-----------------------|-------------------------------------|
| id                   | bigint        | ID элемента           | PRIMARY                             |
| marketplace_order_id | bigint        | ID заказа             | FOREIGN KEY (marketplace_orders.id) |
| marketplace_item_id  | integer       | ID товара             |                                     |
| storage_barcode      | string        | Штрих-код склада      | NULLABLE                            |
| shelf_id             | bigint        | ID стеллажа           | FOREIGN KEY (shelves.id), NULLABLE  |
| quantity             | integer       | Количество            |                                     |
| price                | decimal(10,2) | Цена                  |                                     |
| status               | integer       | Статус                | DEFAULT 0                           |
| seamstress_id        | integer       | ID швеи               | DEFAULT 0                           |
| cutter_id            | bigint        | ID резчика            | FOREIGN KEY (users.id), NULLABLE    |
| completed_at         | timestamp     | Дата завершения       | NULLABLE                            |
| cutting_completed_at | timestamp     | Дата завершения резки | NULLABLE                            |
| created_at           | timestamp     | Дата создания         |                                     |
| updated_at           | timestamp     | Дата обновления       |                                     |

### Связи:

- `marketplace_order_id` → `marketplace_orders.id` (многие к одному, CASCADE
  DELETE)
- `cutter_id` → `users.id` (многие к одному, RESTRICT)
- `shelf_id` → `shelves.id` (многие к одному, RESTRICT)

## marketplace_items - Товары маркетплейсов

**Файл миграции:** `2025_04_06_162201_create_marketplace_items_table.php`

### Структура таблицы:

| Колонка    | Тип         | Описание        | Индекс/Ключ |
|------------|-------------|-----------------|-------------|
| id         | bigint      | ID товара       | PRIMARY     |
| title      | string(255) | Название товара |             |
| width      | integer     | Ширина          |             |
| height     | integer     | Высота          |             |
| created_at | timestamp   | Дата создания   |             |
| updated_at | timestamp   | Дата обновления |             |

## marketplace_order_history - История изменений заказов

**Файл миграции:**
`2025_10_05_100628_create_marketplace_order_history_table.php`

### Структура таблицы:

| Колонка                   | Тип         | Описание           | Индекс/Ключ                              |
|---------------------------|-------------|--------------------|------------------------------------------|
| id                        | bigint      | ID записи          | PRIMARY                                  |
| marketplace_order_id      | bigint      | ID заказа          | FOREIGN KEY (marketplace_orders.id)      |
| marketplace_order_item_id | bigint      | ID элемента заказа | FOREIGN KEY (marketplace_order_items.id) |
| status                    | string(255) | Статус             |                                          |
| created_at                | timestamp   | Дата создания      |                                          |
| updated_at                | timestamp   | Дата обновления    |                                          |

### Связи:

- `marketplace_order_id` → `marketplace_orders.id` (многие к одному, CASCADE
  DELETE)
- `marketplace_order_item_id` → `marketplace_order_items.id` (многие к одному,
  CASCADE DELETE)

## marketplace_supplies - Поставки на маркетплейсы

**Файл миграции:** `2025_07_03_074840_create_marketplace_supplies_table.php`

### Структура таблицы:

| Колонка        | Тип         | Описание                    | Индекс/Ключ      |
|----------------|-------------|-----------------------------|------------------|
| id             | bigint      | ID поставки                 | PRIMARY          |
| supply_id      | string(255) | ID поставки на маркетплейсе | UNIQUE, NULLABLE |
| marketplace_id | integer     | ID маркетплейса             |                  |
| status         | integer     | Статус                      | DEFAULT 0        |
| video          | string      | Видео                       | NULLABLE         |
| completed_at   | timestamp   | Дата завершения             | NULLABLE         |
| created_at     | timestamp   | Дата создания               |                  |
| updated_at     | timestamp   | Дата обновления             |                  |

### Связи:

- Связь с `marketplace_orders` через `supply_id` (один ко многим)

### Особенности структуры:

1. **Заказы с маркетплейсов** поддерживают:
    - Разные типы выполнения (FBS/FBO)
    - Отслеживание статусов
    - Привязку к поставкам
    - Отметку о печати
    - Даты возврата

2. **Элементы заказов** содержат:
    - Информацию о responsible сотрудниках (швея, резчик)
    - Местоположение на складе (стеллаж, штрих-код)
    - Отслеживание времени выполнения этапов

3. **История изменений** позволяет отслеживать все статусы заказов и элементов

4. **Поставки** группируют заказы и могут содержать видео-инструкции
