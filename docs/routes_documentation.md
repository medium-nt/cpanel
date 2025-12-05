# Документация маршрутов Laravel приложения

## Обзор архитектуры маршрутизации

Приложение имеет сложную структуру маршрутизации с разделением на несколько
уровней доступа:

1. **Основной файл маршрутов**: `routes/web.php` - определяет базовые маршруты и
   структуру приложения
2. **API маршруты**: `routes/api.php` - обработка webhook'ов
3. **Консольные команды**: `routes/console.php` - планировщик задач
4. **Модульные маршруты**: 26 файлов с логикой разделения по функциональности

### Ключевые особенности архитектуры:

- **Префикс `/megatulle`**: Все основные маршруты приложения используют этот
  префикс
- **Двухуровневая система доступа**:
    - Маршруты доступные без начала смены (базовый функционал)
    - Маршруты требующие открытой смены (основной функционал)
- **Авторизация через Gates**: Используется Laravel Gates для контроля доступа
- **Middleware**: `auth` - базовая аутентификация, `require_open_shift` -
  требует открытую смену

---

## Детальное описание файлов маршрутов

### 1. Основные маршруты (`web.php`)

#### Публичные маршруты:

- `GET /` - Welcome page
- `GET /autologin/{email}` - Автологин (только для local окружения)
- `GET /home` - Главная страница после авторизации

#### Функциональные маршруты:

- `GET /sticker_printing` - Печать стикеров
- `GET /open_close_work_shift` - Открытие/закрытие смены
- `GET /open_close_work_shift_admin/{user}` - Управление сменой администратора
- `GET /barcode` - Генерация штрихкода
- `GET /fbo_barcode` - FBO штрихкоды
- `PUT /done/{marketplace_order_item}` - Завершение элемента заказа

#### Группы маршрутов:

**Базовый функционал (без смены)**:

- `/megatulle/transactions` - Транзакции
- `/megatulle/profile` - Профиль пользователя
- `/megatulle/users` - Управление пользователями

**Основной функционал (требует смену)**:
Все модули приложения (материалы, поставщики, заказы, инвентаризация и т.д.)

---

### 2. API маршруты (`api.php`)

#### Webhook интеграция:

- `POST /telegram/webhook` - Обработка webhook'ов от Telegram

---

### 3. Консольные команды (`console.php`)

#### Планировщик задач:

- **Каждые 5 минут**: Обновление товаров на маркетплейсах
- **Ежедневно в рабочее время**: Отправка сообщений сотрудникам
- **Ежедневно в 00:01**: Проверка незакрытых смен
- **Ежедневно в 00:25-00:45**: Начисление зарплат по категориям
- **Ежедневно в 01:00**: Удаление старых видео
- **Каждую минуту**: Обработка очереди задач

---

## Сводная таблица всех маршрутов

### Модуль: Управление пользователями (`users.php`)

| Метод  | URL                                       | Имя маршрута            | Действие                | Middleware               |
|--------|-------------------------------------------|-------------------------|-------------------------|--------------------------|
| GET    | /megatulle/users                          | users.index             | Список пользователей    | auth, require_open_shift |
| GET    | /megatulle/users/create                   | users.create            | Форма создания          | auth, require_open_shift |
| POST   | /megatulle/users/store                    | users.store             | Сохранение пользователя | auth, require_open_shift |
| GET    | /megatulle/users/{user}/edit              | users.edit              | Редактирование          | auth, require_open_shift |
| PUT    | /megatulle/users/update/{user}            | users.update            | Обновление              | auth, require_open_shift |
| PUT    | /megatulle/users/motivation_update/{user} | users.motivation_update | Обновление мотивации    | auth, require_open_shift |
| PUT    | /megatulle/users/rate_update/{user}       | users.rate_update       | Обновление рейтинга     | auth, require_open_shift |
| DELETE | /megatulle/users/delete/{user}            | users.destroy           | Удаление                | auth, require_open_shift |
| GET    | /megatulle/users/{user}/get_barcode       | users.get_barcode       | Штрихкод пользователя   | auth, require_open_shift |

### Модуль: Профиль (`profile.php`)

| Метод | URL                             | Имя маршрута         | Действие            | Middleware |
|-------|---------------------------------|----------------------|---------------------|------------|
| GET   | /megatulle/profile              | profile              | Просмотр профиля    | auth       |
| PUT   | /megatulle/profile              | profile.update       | Обновление профиля  | auth       |
| GET   | /megatulle/profile/disconnectTg | profile.disconnectTg | Отключение Telegram | auth       |

### Модуль: Транзакции (`transactions.php`)

| Метод  | URL                                          | Имя маршрута                     | Действие              | Middleware |
|--------|----------------------------------------------|----------------------------------|-----------------------|------------|
| GET    | /megatulle/transactions                      | transactions.index               | Список транзакций     | auth       |
| GET    | /megatulle/transactions/create/{type}        | transactions.create              | Создание транзакции   | auth       |
| POST   | /megatulle/transactions/store                | transactions.store               | Сохранение транзакции | auth       |
| DELETE | /megatulle/transactions/delete/{transaction} | transactions.destroy             | Удаление транзакции   | auth       |
| GET    | /megatulle/transactions/payout_salary        | transactions.payout_salary       | Выплата зарплаты      | auth       |
| POST   | /megatulle/transactions/store_payout_salary  | transactions.store_payout_salary | Сохранение выплаты    | auth       |
| GET    | /megatulle/transactions/payout_bonus         | transactions.payout_bonus        | Выплата бонуса        | auth       |
| POST   | /megatulle/transactions/store_payout_bonus   | transactions.store_payout_bonus  | Сохранение бонуса     | auth       |

### Модуль: Материалы (`materials.php`)

| Метод  | URL                                    | Имя маршрута      | Действие           | Middleware               |
|--------|----------------------------------------|-------------------|--------------------|--------------------------|
| GET    | /megatulle/materials                   | materials.index   | Список материалов  | auth, require_open_shift |
| GET    | /megatulle/materials/create            | materials.create  | Создание материала | auth, require_open_shift |
| POST   | /megatulle/materials/store             | materials.store   | Сохранение         | auth, require_open_shift |
| GET    | /megatulle/materials/{material}/edit   | materials.edit    | Редактирование     | auth, require_open_shift |
| PUT    | /megatulle/materials/update/{material} | materials.update  | Обновление         | auth, require_open_shift |
| DELETE | /megatulle/materials/delete/{material} | materials.destroy | Удаление           | auth, require_open_shift |

### Модуль: Поставщики (`suppliers.php`)

| Метод  | URL                                    | Имя маршрута      | Действие            | Middleware               |
|--------|----------------------------------------|-------------------|---------------------|--------------------------|
| GET    | /megatulle/suppliers                   | suppliers.index   | Список поставщиков  | auth, require_open_shift |
| GET    | /megatulle/suppliers/create            | suppliers.create  | Создание поставщика | auth, require_open_shift |
| POST   | /megatulle/suppliers/store             | suppliers.store   | Сохранение          | auth, require_open_shift |
| GET    | /megatulle/suppliers/{supplier}/edit   | suppliers.edit    | Редактирование      | auth, require_open_shift |
| PUT    | /megatulle/suppliers/update/{supplier} | suppliers.update  | Обновление          | auth, require_open_shift |
| DELETE | /megatulle/suppliers/delete/{supplier} | suppliers.destroy | Удаление            | auth, require_open_shift |

### Модуль: Движения от поставщика (`movements_from_supplier.php`)

| Метод  | URL                                                  | Имя маршрута                    | Действие               | Middleware               |
|--------|------------------------------------------------------|---------------------------------|------------------------|--------------------------|
| GET    | /megatulle/movements_from_supplier                   | movements_from_supplier.index   | Список поступлений     | auth, require_open_shift |
| GET    | /megatulle/movements_from_supplier/create            | movements_from_supplier.create  | Поступление материалов | auth, require_open_shift |
| POST   | /megatulle/movements_from_supplier/store             | movements_from_supplier.store   | Сохранение             | auth, require_open_shift |
| GET    | /megatulle/movements_from_supplier/{order}/edit      | movements_from_supplier.edit    | Редактирование         | auth, require_open_shift |
| PUT    | /megatulle/movements_from_supplier/update/{order}    | movements_from_supplier.update  | Обновление             | auth, require_open_shift |
| DELETE | /megatulle/movements_from_supplier/delete/{movement} | movements_from_supplier.destroy | Удаление               | auth, require_open_shift |

### Модуль: Движения в цех (`movements_to_workshop.php`)

| Метод | URL                                                   | Имя маршрута                         | Действие            | Middleware               |
|-------|-------------------------------------------------------|--------------------------------------|---------------------|--------------------------|
| GET   | /megatulle/movements_to_workshop                      | movements_to_workshop.index          | Список перемещений  | auth, require_open_shift |
| GET   | /megatulle/movements_to_workshop/create               | movements_to_workshop.create         | Создание            | auth, require_open_shift |
| POST  | /megatulle/movements_to_workshop/store                | movements_to_workshop.store          | Сохранение          | auth, require_open_shift |
| GET   | /megatulle/movements_to_workshop/{order}/collect      | movements_to_workshop.collect        | Сбор материалов     | auth, require_open_shift |
| PUT   | /megatulle/movements_to_workshop/save_collect/{order} | movements_to_workshop.save_collect   | Сохранение сбора    | auth, require_open_shift |
| GET   | /megatulle/movements_to_workshop/{order}/receive      | movements_to_workshop.receive        | Приемка             | auth, require_open_shift |
| PUT   | /megatulle/movements_to_workshop/save_receive/{order} | movements_to_workshop.save_receive   | Сохранение приемки  | auth, require_open_shift |
| GET   | /megatulle/movements_to_workshop/write_off            | movements_to_workshop.write_off      | Списание            | auth, require_open_shift |
| POST  | /megatulle/movements_to_workshop/save_write_off       | movements_to_workshop.save_write_off | Сохранение списания | auth, require_open_shift |
| GET   | /megatulle/movements_to_workshop/{order}/delete       | movements_to_workshop.destroy        | Удаление            | auth, require_open_shift |

### Модуль: Инвентаризация (`inventory.php`)

| Метод  | URL                                     | Имя маршрута               | Действие              | Middleware               |
|--------|-----------------------------------------|----------------------------|-----------------------|--------------------------|
| GET    | /megatulle/inventory/warehouse          | inventory.warehouse        | Инвентаризация склада | auth, require_open_shift |
| GET    | /megatulle/inventory/workshop           | inventory.workshop         | Инвентаризация цеха   | auth, require_open_shift |
| GET    | /megatulle/inventory/inventory_checks   | inventory.inventory_checks | Список проверок       | auth, require_open_shift |
| GET    | /megatulle/inventory/{inventory}/show   | inventory.show             | Детали проверки       | auth, require_open_shift |
| GET    | /megatulle/inventory/create             | inventory.create           | Создание проверки     | auth, require_open_shift |
| POST   | /megatulle/inventory/store              | inventory.store            | Сохранение проверки   | auth, require_open_shift |
| DELETE | /megatulle/inventory/delete/{inventory} | inventory.destroy          | Удаление проверки     | auth, require_open_shift |

### Модуль: Товары маркетплейсов (`marketplace_items.php`)

| Метод  | URL                                                    | Имя маршрута              | Действие        | Middleware               |
|--------|--------------------------------------------------------|---------------------------|-----------------|--------------------------|
| GET    | /megatulle/marketplace_items                           | marketplace_items.index   | Список товаров  | auth, require_open_shift |
| GET    | /megatulle/marketplace_items/create                    | marketplace_items.create  | Создание товара | auth, require_open_shift |
| POST   | /megatulle/marketplace_items/store                     | marketplace_items.store   | Сохранение      | auth, require_open_shift |
| GET    | /megatulle/marketplace_items/{marketplace_item}/edit   | marketplace_items.edit    | Редактирование  | auth, require_open_shift |
| PUT    | /megatulle/marketplace_items/update/{marketplace_item} | marketplace_items.update  | Обновление      | auth, require_open_shift |
| DELETE | /megatulle/marketplace_items/delete/{marketplace_item} | marketplace_items.destroy | Удаление        | auth, require_open_shift |

### Модуль: Заказы маркетплейсов (`marketplace_orders.php`)

| Метод  | URL                                                        | Имя маршрута                | Действие         | Middleware               |
|--------|------------------------------------------------------------|-----------------------------|------------------|--------------------------|
| GET    | /megatulle/marketplace_orders                              | marketplace_orders.index    | Список заказов   | auth, require_open_shift |
| GET    | /megatulle/marketplace_orders/create                       | marketplace_orders.create   | Создание заказа  | auth, require_open_shift |
| POST   | /megatulle/marketplace_orders/store                        | marketplace_orders.store    | Сохранение       | auth, require_open_shift |
| GET    | /megatulle/marketplace_orders/{marketplace_order}/edit     | marketplace_orders.edit     | Редактирование   | auth, require_open_shift |
| PUT    | /megatulle/marketplace_orders/update/{marketplace_order}   | marketplace_orders.update   | Обновление       | auth, require_open_shift |
| GET    | /megatulle/marketplace_orders/{marketplace_order}/complete | marketplace_orders.complete | Завершение       | auth, require_open_shift |
| DELETE | /megatulle/marketplace_orders/delete/{marketplace_order}   | marketplace_orders.destroy  | Удаление         | auth, require_open_shift |
| DELETE | /megatulle/marketplace_orders/{marketplace_order}/remove   | marketplace_orders.remove   | Удаление позиции | auth, require_open_shift |

### Модуль: Элементы заказов (`marketplace_order_items.php`)

| Метод | URL                                                                          | Имя маршрута                            | Действие         | Middleware               |
|-------|------------------------------------------------------------------------------|-----------------------------------------|------------------|--------------------------|
| GET   | /megatulle/marketplace_order_items                                           | marketplace_order_items.index           | Список элементов | auth, require_open_shift |
| GET   | /megatulle/marketplace_order_items/get_new                                   | marketplace_order_items.getNewOrderItem | Новые элементы   | auth, require_open_shift |
| PUT   | /megatulle/marketplace_order_items/labeling/{marketplace_order_item}         | marketplace_order_items.labeling        | Маркировка       | auth, require_open_shift |
| PUT   | /megatulle/marketplace_order_items/complete_cutting/{marketplace_order_item} | marketplace_order_items.completeCutting | Завершение резки | auth, require_open_shift |
| PUT   | /megatulle/marketplace_order_items/cancel/{marketplace_order_item}           | marketplace_order_items.cancel          | Отмена           | auth, require_open_shift |
| GET   | /megatulle/marketplace_order_items/print_cutting                             | marketplace_order_items.printCutting    | Печать резки     | auth, require_open_shift |

### Модуль: API маркетплейсов (`marketplace_api.php`)

| Метод | URL                                             | Имя маршрута                       | Действие            | Middleware               |
|-------|-------------------------------------------------|------------------------------------|---------------------|--------------------------|
| GET   | /megatulle/marketplace_api/check_skuz           | marketplace_api.checkSkuz          | Проверка СКУЗ       | auth, require_open_shift |
| GET   | /megatulle/marketplace_api/new_order            | marketplace_api.newOrder           | Новые заказы        | auth, require_open_shift |
| GET   | /megatulle/marketplace_api/check_duplicate_skuz | marketplace_api.checkDuplicateSkuz | Проверка дубликатов | auth, require_open_shift |
| GET   | /megatulle/marketplace_api/check_cancelled      | marketplace_api.check_cancelled    | Проверка отмен      | auth, require_open_shift |

### Модуль: Поставки (`marketplace_supplies.php`)

| Метод  | URL                                                                       | Имя маршрута                              | Действие            | Middleware               |
|--------|---------------------------------------------------------------------------|-------------------------------------------|---------------------|--------------------------|
| GET    | /megatulle/marketplace_supplies                                           | marketplace_supplies.index                | Список поставок     | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/show/{marketplace_supply}                 | marketplace_supplies.show                 | Детали поставки     | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/complete             | marketplace_supplies.complete             | Завершение          | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_id}/create                   | marketplace_supplies.create               | Создание            | auth, require_open_shift |
| DELETE | /megatulle/marketplace_supplies/{marketplace_supply}/destroy              | marketplace_supplies.destroy              | Удаление            | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/get_docs             | marketplace_supplies.get_docs             | Документы           | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/get_barcode          | marketplace_supplies.get_barcode          | Штрихкод            | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/update_status_orders | marketplace_supplies.update_status_orders | Обновление статусов | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/done                 | marketplace_supplies.done                 | Выполнено           | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/close                | marketplace_supplies.close                | Закрытие            | auth, require_open_shift |
| GET    | /megatulle/marketplace_supplies/{marketplace_supply}/delete_video         | marketplace_supplies.delete_video         | Удаление видео      | auth, require_open_shift |
| POST   | /megatulle/marketplace_supplies/upload-chunk                              | marketplace_supplies.upload-chunk         | Загрузка файла      | auth, require_open_shift |

### Модуль: Склад товаров (`warehouse_of_item.php`)

| Метод | URL                                                                                | Имя маршрута                         | Действие            | Middleware               |
|-------|------------------------------------------------------------------------------------|--------------------------------------|---------------------|--------------------------|
| GET   | /megatulle/warehouse_of_item                                                       | warehouse_of_item.index              | Склад               | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/new_refunds                                           | warehouse_of_item.new_refunds        | Новые возвраты      | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/add_group                                             | warehouse_of_item.add_group          | Добавление группы   | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/save_group                                            | warehouse_of_item.save_group         | Сохранение группы   | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/storage_barcode                                       | warehouse_of_item.storage_barcode    | Штрихкод хранения   | auth, require_open_shift |
| POST  | /megatulle/warehouse_of_item/save_storage/{marketplace_item}                       | warehouse_of_item.save_storage       | Сохранение хранения | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/to_pick_list                                          | warehouse_of_item.to_pick_list       | Сборочный лист      | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/to_pick_list_print                                    | warehouse_of_item.to_pick_list_print | Печать листа        | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/to_pick/{order}                                       | warehouse_of_item.to_pick            | К сборке            | auth, require_open_shift |
| PUT   | /megatulle/warehouse_of_item/labeling/{marketplace_order}/{marketplace_order_item} | warehouse_of_item.labeling           | Маркировка          | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/done/{marketplace_order}                              | warehouse_of_item.done               | Завершено           | auth, require_open_shift |
| GET   | /megatulle/warehouse_of_item/to_work/{marketplace_order}                           | warehouse_of_item.to_work            | В работу            | auth, require_open_shift |

### Модуль: Стеллажи (`shelves.php`)

| Метод  | URL                               | Имя маршрута    | Действие          | Middleware               |
|--------|-----------------------------------|-----------------|-------------------|--------------------------|
| GET    | /megatulle/shelves                | shelves.index   | Список стеллажей  | auth, require_open_shift |
| GET    | /megatulle/shelves/create         | shelves.create  | Создание стеллажа | auth, require_open_shift |
| POST   | /megatulle/shelves/store          | shelves.store   | Сохранение        | auth, require_open_shift |
| GET    | /megatulle/shelves/{shelf}/edit   | shelves.edit    | Редактирование    | auth, require_open_shift |
| PUT    | /megatulle/shelves/update/{shelf} | shelves.update  | Обновление        | auth, require_open_shift |
| DELETE | /megatulle/shelves/delete/{shelf} | shelves.destroy | Удаление          | auth, require_open_shift |

### Модуль: Настройки (`setting.php`)

| Метод | URL                           | Имя маршрута       | Действие           | Middleware               |
|-------|-------------------------------|--------------------|--------------------|--------------------------|
| GET   | /megatulle/setting            | setting.index      | Настройки          | auth, require_open_shift |
| POST  | /megatulle/setting/save       | setting.save       | Сохранение         | auth, require_open_shift |
| GET   | /megatulle/setting/test       | setting.test       | Тест               | auth, require_open_shift |
| GET   | /megatulle/setting/salary     | setting.salary     | Настройки зарплаты | auth, require_open_shift |
| GET   | /megatulle/setting/duplicates | setting.duplicates | Дубликаты          | auth, require_open_shift |

### Модуль: График (`schedule.php`)

| Метод | URL                            | Имя маршрута        | Действие       | Middleware               |
|-------|--------------------------------|---------------------|----------------|--------------------------|
| POST  | /megatulle/schedule/changeDate | schedule.changeDate | Изменение даты | auth, require_open_shift |

### Модуль: Расход материалов (`material_consumption.php`)

| Метод | URL                                                           | Имя маршрута                 | Действие | Middleware               |
|-------|---------------------------------------------------------------|------------------------------|----------|--------------------------|
| GET   | /megatulle/material_consumption/delete/{material_consumption} | material_consumption.destroy | Удаление | auth, require_open_shift |

### Модуль: Бракованные материалы (`defect_materials.php`)

| Метод  | URL                                                | Имя маршрута                    | Действие             | Middleware               |
|--------|----------------------------------------------------|---------------------------------|----------------------|--------------------------|
| GET    | /megatulle/defect_materials                        | defect_materials.index          | Список брака         | auth, require_open_shift |
| GET    | /megatulle/defect_materials/create                 | defect_materials.create         | Создание             | auth, require_open_shift |
| POST   | /megatulle/defect_materials/store                  | defect_materials.store          | Сохранение           | auth, require_open_shift |
| GET    | /megatulle/defect_materials/{order}/approve_reject | defect_materials.approve_reject | Одобрение/отклонение | auth, require_open_shift |
| GET    | /megatulle/defect_materials/{order}/save           | defect_materials.save           | Сохранение           | auth, require_open_shift |
| GET    | /megatulle/defect_materials/{order}/pick_up        | defect_materials.pick_up        | Подбор               | auth, require_open_shift |
| DELETE | /megatulle/defect_materials/{order}/delete         | defect_materials.delete         | Удаление             | auth, require_open_shift |

### Модуль: Движения брака поставщику (`movements_defect_to_supplier.php`)

| Метод | URL                                            | Имя маршрута                        | Действие   | Middleware               |
|-------|------------------------------------------------|-------------------------------------|------------|--------------------------|
| GET   | /megatulle/movements_defect_to_supplier        | movements_defect_to_supplier.index  | Список     | auth, require_open_shift |
| GET   | /megatulle/movements_defect_to_supplier/create | movements_defect_to_supplier.create | Создание   | auth, require_open_shift |
| POST  | /megatulle/movements_defect_to_supplier/store  | movements_defect_to_supplier.store  | Сохранение | auth, require_open_shift |

### Модуль: Списание остатков (`write_off_remnants.php`)

| Метод | URL                                  | Имя маршрута              | Действие   | Middleware               |
|-------|--------------------------------------|---------------------------|------------|--------------------------|
| GET   | /megatulle/write_off_remnants        | write_off_remnants.index  | Список     | auth, require_open_shift |
| GET   | /megatulle/write_off_remnants/create | write_off_remnants.create | Создание   | auth, require_open_shift |
| POST  | /megatulle/write_off_remnants/store  | write_off_remnants.store  | Сохранение | auth, require_open_shift |

### Модуль: Движения по заказам (`movements_by_marketplace_order.php`)

| Метод | URL                                       | Имя маршрута                         | Действие | Middleware               |
|-------|-------------------------------------------|--------------------------------------|----------|--------------------------|
| GET   | /megatulle/movements_by_marketplace_order | movements_by_marketplace_order.index | Список   | auth, require_open_shift |

### Модуль: Зарплата (`salary.php`)

| Метод | URL               | Имя маршрута        | Действие        | Middleware               |
|-------|-------------------|---------------------|-----------------|--------------------------|
| GET   | /megatulle/salary | transactions.salary | Таблица зарплат | auth, require_open_shift |

---

## Особенности и примечания

### 1. Система авторизации

- Используются Laravel Gates для контроля доступа
- Каждый маршрут проверяет разрешения: `can('action', Model::class)`
- Различные уровни доступа для разных ролей

### 2. Middleware структура

- `auth` - базовая аутентификация
- `require_open_shift` - требует открытую рабочую смену

### 3. Именование маршрутов

- Все маршруты имеют осмысленные имена
- Используется dot-нотация: `module.action`
- Префикс `megatulle` для всех основных маршрутов

### 4. RESTful подход

- Большинство модулей следует RESTful конвенциям
- Стандартные операции: index, create, store, edit, update, destroy
- Дополнительные действия для специфической логики

### 5. Параметры маршрутов

- Используются id для идентификации ресурсов
- Некоторые маршруты принимают дополнительные параметры (type в транзакциях)

### 6. Особые маршруты

- Файл загрузки по частям для видео поставок
- Генерация штрихкодов для различных сущностей
- Интеграция с маркетплейсами через API

---

Дата последнего обновления: 2025-12-04
