# Таблицы производства

## orders - Производственные заказы

**Файл миграции:** `2025_04_03_090246_create_orders_table.php`

### Структура таблицы:

| Колонка              | Тип       | Описание               | Индекс/Ключ                                   |
|----------------------|-----------|------------------------|-----------------------------------------------|
| id                   | bigint    | ID заказа              | PRIMARY                                       |
| type_movement        | integer   | Тип движения           | DEFAULT 0                                     |
| status               | integer   | Статус                 | DEFAULT 0                                     |
| supplier_id          | bigint    | ID поставщика          | FOREIGN KEY (suppliers.id), NULLABLE          |
| storekeeper_id       | bigint    | ID кладовщика          | FOREIGN KEY (users.id), NULLABLE              |
| seamstress_id        | bigint    | ID швеи                | FOREIGN KEY (users.id), NULLABLE              |
| cutter_id            | bigint    | ID резчика             | FOREIGN KEY (users.id), NULLABLE              |
| marketplace_order_id | bigint    | ID заказа маркетплейса | FOREIGN KEY (marketplace_orders.id), NULLABLE |
| comment              | text      | Комментарий            | NULLABLE                                      |
| is_approved          | integer   | Одобрен ли заказ       | DEFAULT 0                                     |
| completed_at         | timestamp | Дата завершения        | NULLABLE                                      |
| created_at           | timestamp | Дата создания          |                                               |
| updated_at           | timestamp | Дата обновления        |                                               |

### Связи:

- `supplier_id` → `suppliers.id` (многие к одному, RESTRICT)
- `storekeeper_id` → `users.id` (многие к одному, RESTRICT)
- `seamstress_id` → `users.id` (многие к одному, RESTRICT)
- `cutter_id` → `users.id` (многие к одному, RESTRICT)
- `marketplace_order_id` → `marketplace_orders.id` (многие к одному, RESTRICT)

## inventory_checks - Инвентаризации

**Файл миграции:** `2025_11_05_170142_create_inventory_checks_table.php`

### Структура таблицы:

| Колонка     | Тип       | Описание                    | Индекс/Ключ           |
|-------------|-----------|-----------------------------|-----------------------|
| id          | bigint    | ID инвентаризации           | PRIMARY               |
| status      | enum      | Статус (in_progress/closed) | DEFAULT 'in_progress' |
| comment     | text      | Комментарий                 | NULLABLE              |
| finished_at | timestamp | Дата завершения             | NULLABLE              |
| created_at  | timestamp | Дата создания               |                       |
| updated_at  | timestamp | Дата обновления             |                       |

### Связи:

- Связь с `inventory_check_items` (один ко многим)

## inventory_check_items - Элементы инвентаризации

**Файл миграции:** `2025_11_05_170613_create_inventory_check_items_table.php`

### Структура таблицы:

| Колонка                   | Тип       | Описание           | Индекс/Ключ                                       |
|---------------------------|-----------|--------------------|---------------------------------------------------|
| id                        | bigint    | ID элемента        | PRIMARY                                           |
| inventory_check_id        | bigint    | ID инвентаризации  | FOREIGN KEY (inventory_checks.id), CASCADE        |
| marketplace_order_item_id | bigint    | ID элемента заказа | FOREIGN KEY (marketplace_order_items.id), CASCADE |
| expected_shelf_id         | bigint    | Ожидаемый стеллаж  | FOREIGN KEY (shelves.id), NULLABLE                |
| founded_shelf_id          | bigint    | Найденный стеллаж  | FOREIGN KEY (shelves.id), NULLABLE                |
| is_found                  | boolean   | Найден ли          | DEFAULT false                                     |
| is_added_later            | boolean   | Добавлен позже     | DEFAULT false                                     |
| created_at                | timestamp | Дата создания      |                                                   |
| updated_at                | timestamp | Дата обновления    |                                                   |

### Связи:

- `inventory_check_id` → `inventory_checks.id` (многие к одному, CASCADE DELETE)
- `marketplace_order_item_id` → `marketplace_order_items.id` (многие к одному,
  CASCADE DELETE)
- `expected_shelf_id` → `shelves.id` (многие к одному)
- `founded_shelf_id` → `shelves.id` (многие к одному)

### Уникальные индексы:

- `inventory_check_id`, `marketplace_order_item_id` - уникальная комбинация для
  предотвращения дублирования

### Особенности структуры:

1. **Производственные заказы** отслеживают:
    - Все этапы производства (кладовщик, резчик, швея)
    - Связь с поставщиками материалов
    - Связь с заказами маркетплейсов
    - Статусы и согласование

2. **Инвентаризации** позволяют:
    - Проводить проверки наличия товаров
    - Сравнивать ожидаемое и фактическое местоположение
    - Отмечать товары, добавленные в процессе проверки
    - Хранить историю инвентаризаций

3. **Элементы инвентаризации** содержат детальную информацию:
    - Какой товар проверялся
    - Где он должен был быть
    - Где он был найден
    - Был ли вообще найден

Все таблицы обеспечивают полный цикл управления производством от заказа до
контроля остатков.
