# Таблицы материалов и складского учета

## type_materials - Типы материалов

**Файл миграции:** `2025_04_01_112450_create_type_materials_table.php`

### Структура таблицы:

| Колонка    | Тип         | Описание        | Индекс/Ключ |
|------------|-------------|-----------------|-------------|
| id         | bigint      | ID типа         | PRIMARY     |
| title      | string(255) | Название типа   | UNIQUE      |
| created_at | timestamp   | Дата создания   |             |
| updated_at | timestamp   | Дата обновления |             |

### Связи:

- Связь с `materials` через `type_id` (один ко многим)

## materials - Материалы

**Файл миграции:** `2025_04_01_113225_create_materials_table.php`

### Структура таблицы:

| Колонка    | Тип         | Описание                    | Индекс/Ключ                               |
|------------|-------------|-----------------------------|-------------------------------------------|
| id         | bigint      | ID материала                | PRIMARY                                   |
| title      | string(255) | Название материала          | UNIQUE                                    |
| type_id    | bigint      | ID типа материала           | FOREIGN KEY (type_materials.id), NULLABLE |
| height     | integer     | Высота                      | NULLABLE                                  |
| unit       | string(255) | Единица измерения           |                                           |
| created_at | timestamp   | Дата создания               |                                           |
| updated_at | timestamp   | Дата обновления             |                                           |
| deleted_at | timestamp   | Дата удаления (soft delete) |                                           |

### Связи:

- `type_id` → `type_materials.id` (многие к одному, RESTRICT)
- Связь с `movement_materials` (один ко многим)
- Связь с `material_consumptions` (один ко многим)

## movement_materials - Движения материалов

**Файл миграции:** `2025_04_06_140355_create_movement_materials_table.php`

### Структура таблицы:

| Колонка          | Тип           | Описание              | Индекс/Ключ                       |
|------------------|---------------|-----------------------|-----------------------------------|
| id               | bigint        | ID движения           | PRIMARY                           |
| material_id      | bigint        | ID материала          | FOREIGN KEY (materials.id)        |
| quantity         | decimal(10,0) | Количество            | DEFAULT 0                         |
| ordered_quantity | decimal(10,0) | Заказанное количество | DEFAULT 0                         |
| price            | decimal(10,0) | Цена                  | DEFAULT 0                         |
| order_id         | bigint        | ID заказа             | FOREIGN KEY (orders.id), NULLABLE |
| created_at       | timestamp     | Дата создания         |                                   |
| updated_at       | timestamp     | Дата обновления       |                                   |

### Связи:

- `material_id` → `materials.id` (многие к одному, RESTRICT)
- `order_id` → `orders.id` (многие к одному, RESTRICT)

## material_consumptions - Расход материалов

**Файл миграции:** `2025_04_10_053457_create_material_consumptions_table.php`

### Структура таблицы:

| Колонка     | Тип           | Описание        | Индекс/Ключ                        |
|-------------|---------------|-----------------|------------------------------------|
| id          | bigint        | ID расхода      | PRIMARY                            |
| item_id     | bigint        | ID товара       | FOREIGN KEY (marketplace_items.id) |
| material_id | bigint        | ID материала    | FOREIGN KEY (materials.id)         |
| quantity    | decimal(10,0) | Количество      |                                    |
| created_at  | timestamp     | Дата создания   |                                    |
| updated_at  | timestamp     | Дата обновления |                                    |

### Связи:

- `item_id` → `marketplace_items.id` (многие к одному, CASCADE DELETE)
- `material_id` → `materials.id` (многие к одному, CASCADE DELETE)

## skus - SKU товаров

**Файл миграции:** `2025_04_10_032014_create_sku_table.php`

### Структура таблицы:

| Колонка        | Тип         | Описание        | Индекс/Ключ                        |
|----------------|-------------|-----------------|------------------------------------|
| id             | bigint      | ID SKU          | PRIMARY                            |
| item_id        | bigint      | ID товара       | FOREIGN KEY (marketplace_items.id) |
| sku            | string(255) | Артикул (SKU)   |                                    |
| marketplace_id | integer     | ID маркетплейса |                                    |
| created_at     | timestamp   | Дата создания   |                                    |
| updated_at     | timestamp   | Дата обновления |                                    |

### Связи:

- `item_id` → `marketplace_items.id` (многие к одному, CASCADE DELETE)

## stacks - Стопки материалов

**Файл миграции:** `2025_05_29_144332_create_stacks_table.php`

### Структура таблицы:

| Колонка       | Тип       | Описание        | Индекс/Ключ            |
|---------------|-----------|-----------------|------------------------|
| id            | bigint    | ID стопки       | PRIMARY                |
| seamstress_id | bigint    | ID швеи         | FOREIGN KEY (users.id) |
| stack         | integer   | Текущая стопка  | DEFAULT 0              |
| max           | integer   | Максимум        | DEFAULT 0              |
| created_at    | timestamp | Дата создания   |                        |
| updated_at    | timestamp | Дата обновления |                        |

### Связи:

- `seamstress_id` → `users.id` (многие к одному, CASCADE DELETE)

### Особенности структуры:

1. **Типы материалов** - справочник для классификации материалов

2. **Материалы** содержат:
    - Основные характеристики (название, высота, единицы)
    - Классификацию по типам
    - Soft delete для сохранения истории

3. **Движения материалов** отслеживают:
    - Приход/расход материалов
    - Заказанные количества
    - Цены
    - Привязку к заказам

4. **Расход материалов** связывают товары с необходимыми материалами
    - Позволяют рассчитывать потребность в материалах
    - Учитывают расход на единицу товара

5. **SKU** предоставляют уникальные идентификаторы товаров для разных
   маркетплейсов

6. **Стопки материалов** позволяют отслеживать рабочую нагрузку швей
