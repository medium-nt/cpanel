# Warehouse Operations — Складские операции

> Last reviewed: 2026-07-02

## Обзор

Складская система управляет хранением товаров на стеллажах, инвентаризацией,
стикеровкой и поиском по штрихкодам. Поддерживается несколько форматов
штрихкодов для разных маркетплейсов (Ozon FBS, Ozon FBO, WB FBO). Киоск
позволяет быстро проверять статусы заказов по ролям.

## Как это работает

### Инвентаризация (InventoryService)

Методы расчёта остатков:

- `materialInWorkshop()` — остатки материалов в цеху
- `materialInWarehouse()` — остатки на складе
- `defectMaterialInWarehouse()` — бракованные материалы
- `remnantsMaterialInWarehouse()` — остатки/обрезки
- `materialsQuantityByWorkshopPerShift()` — количество по цехам и сменам
- `materialsQuantityByWarehouseFromRolls()` — через рулоны на складе
- Группировка материалов по типам на странице склада

**Фильтрация архива (02.07.2026):**

- Все просмотры остатков фильтруют материалы по флагу `is_archive=false`
- Архивные материалы (`is_archive=true`) полностью скрыты из:
    - Склад (`/megatulle/inventory/`)
    - Брак (`defect_warehouse`)
    - Цех (`/megatulle/inventory/workshop`)
- Scope `Material::notArchived()` применяется в `materialsQuantityByWarehouse()`
  и
  `materialsQuantityByWorkshopAggregate()`
- Бизнес-смысл: архивные материалы недоступны для производства и не видны в
  остатках, но не удаляются из БД

**canArchive — защита перевода в архив (Этап 2):**

- `InventoryService::canArchive(Material)` — проверяет, можно ли перевести
  материал в архив
- Условие: `materialInWarehouse() == 0` И `materialInWorkshop() == 0`
- Используется в `MaterialController::update` для защиты перевода в архив
- Материал можно перевести в «Архив» только из статуса «Нельзя заказать»
  (`is_active=false`)
- Путь статусов: `Активен → Нельзя заказать → Архив`

**Процесс инвентаризации:**

1. Создаётся `InventoryCheck` (статус, комментарий)
2. Для каждого товара на стеллаже создаётся `InventoryCheckItem`
3. Сравнивается ожидаемый стеллаж (`expected_shelf_id`) с фактическим (
   `founded_shelf_id`)
4. Флаг `is_found` — найден ли товар, `is_added_later` — добавлен ли позже

### Визуальное разделение остатков по типам материалов

Остатки материалов сгруппированы по типам на обеих страницах инвентаризации:

#### Склад (`/megatulle/inventory/`)

- **Секции по типам:** материалы сгруппированы в визуальные блоки по `type_id`
- **Заголовки секций:** название типа материала и счётчик позиций
- **Порядок секций:** определяется таблицей `type_materials` для
  последовательного отображения

**Реализация:**

- `InventoryService::materialsQuantityByWarehouseFromRolls()` — добавлен
  `type_id` в select для группировки
- `InventoryController::groupMaterialsByType()` — приватный метод сортирует
  материалы по типам
- `resources/views/inventory/warehouse.blade.php` — обновлён для рендера секций
  с заголовками

#### Цех (`/megatulle/inventory/workshop`)

Аналогичное разделение материалов на странице «Материал на производстве»:

- Каждая секция типа материала рендерится в отдельной card с заголовком
- Заголовок card содержит название типа и badge с количеством позиций
- Порядок секций определяется `TypeMaterial::orderBy('id')`
- Материалы без `type_id` попадают в секцию «Прочее»
- **Обводка рабочих смен сохранена:** CSS-классы
  `.today-shift-col(-top/-bottom)`
  применяются к колонкам смен из `$todayShiftIds` внутри каждой секции
- **Ширина колонок таблиц:** «Материал» = 250px фикс, «Итого» = 150px фикс,
  колонки смен делят остаток поровну (auto). Число смен одинаково во всех
  секциях.

**Реализация:**

- `InventoryService::materialsQuantityByWorkshopPerShift()` — добавлен `type_id`
  в SELECT (строка 276), groupBy (строка 281) и в объект material (строка 291)
- `InventoryController::byWorkshop()` — теперь передаёт `sections` через
  `groupMaterialsByType()` вместо плоского `materials`
- `InventoryController::groupMaterialsByType()` — метод стал переиспользуемым:
  применяется для склада (`byWarehouse`) и цеха (`byWorkshop`); PHPDoc обобщён
- `resources/views/inventory/workshop.blade.php` — desktop-таблица и
  mobile-карточки обёрнуты в `@foreach ($sections as $section)` с card-header

### Хранение на стеллажах (Shelf)

- `Shelf` — простая модель единицы хранения
- Товары (`MarketplaceOrderItem`) привязываются к стеллажу через `shelf_id`
- Используется для организации и быстрого поиска

### Склад товаров (WarehouseOfItemService)

**Основные операции:**

- Фильтрация товаров по статусу, материалу, размерам, стеллажу
- **Excel-экспорт:** стандартный формат и формат WB
- **Генерация штрихкодов хранения:** 13-значный формат с контрольной цифрой (
  алгоритм Луна)
- **Размещение товара:** назначение стеллажа, обновление статуса (11 = на
  складе)
- **Поиск для возврата:** сложный lookup по штрихкоду с поддержкой нескольких
  форматов

### Стикеровка (StickerService и ProductSticker)

**StickerService:**

- Выбор шаблона по названию товара и маркетплейсу
- Специальная обработка для "ВУАЛЬ (БЕЗ УТ)"
- Расчёт размера шрифта по длине текста
- Поддержка форматов Ozon и WB

**ProductSticker:**

- Хранит метаданные: название, цвет, тип печати, материал, страну, тип крепления
- Связан с конкретными товарами маркетплейса

**Страница печати стикеров (`sticker_printing.blade.php`):**

- Фильтрация товаров для стикеровки: материал, ширина, высота, швея, маркетплейс
- Фильтры (материал, ширина, высота) подтягиваются динамически из БД через
  `MarketplaceItemService::getAllTitleMaterials()`,
  `getAllWidthMaterials()`, `getAllHeightMaterials()` — уникальные значения из
  таблицы `marketplace_items`
- При добавлении нового товара в `marketplace_items` его параметры автоматически
  появляются в фильтрах

### Поиск по штрихкоду (BarcodeSearchController)

Поддерживаемые форматы:
| Штрихкод | Формат | Маркетплейс |
|----------|--------|-------------|
| 15 цифр | FBS стикер | Ozon |
| 13 цифр | FBO стикер | Wildberries |
| "ii" префикс | Возвратный стикер | Ozon |
| "OZN" префикс | FBO стикер | Ozon |

Логика:

1. Определяет формат штрихкода
2. Ищет в `MarketplaceOrderItem` по `storage_barcode`
3. Если не найден — ищет через API маркетплейса
4. Фильтрует результаты по статусу

### Фильтрация по штрихкоду хранения (to_pick_list)

**Бизнес-смысл:** Кладовщик может быстро найти все заказы, которые содержат
товар с
определенным `marketplace_item_id`, просто отсканировав `storage_barcode` любого
экземпляра этого товара.

**Ключевая концепция:**

- `storage_barcode` — штрихкод **экземпляра** на полке (уникален)
- `marketplace_item_id` — **артикул** («Бамбук 200х220»)
- При сканировании barcode система находит `marketplace_item_id` и фильтрует все
  заказы, содержащие этот артикул

**Логика фильтрации (`WarehouseOfItemController::toPickList()`):**

```
Сканирован ШК → поиск в MarketplaceOrderItem (storage_barcode, status IN [11,13])
  → Не найден → alert "Товар не найден на складе"
  → Найден → взять marketplace_item_id (артикул)
    → Фильтрация заказов по marketplace_item_id
    → Показать только заказы, содержащие этот товар
```

**UI компоненты:**

- Input для сканирования `storage_barcode` с автофокусом при загрузке страницы
- Query параметр `?storage_barcode=XXX` для фильтрации
- Кнопка сброса фильтра (красный крестик) при активном фильтре
- Alert сообщение если barcode не найден
- Форма отправляется методом GET на `warehouse_of_item.to_pick_list`

**Файлы:**

- `app/Http/Controllers/WarehouseOfItemController.php` — метод `toPickList()`
- `resources/views/warehouse_of_item/to_pick_list.blade.php` — форма поиска

### Сканер подбора товаров (PickupScan)

**Бизнес-смысл:** Кладовщик стоит у полки и сканирует штрихкоды лежащих товаров.
Система мгновенно определяет, годится ли артикул для активного подбора, и
отсекает «лишние» экземпляры.

**Ключевая концепция:**

- `storage_barcode` — штрихкод **экземпляра** на полке (уникален)
- `marketplace_item_id` — **артикул** («Бамбук 200х220»)
- Экземпляры одного артикула **взаимозаменяемы** (подтверждается swap-логикой в
  `WarehouseOfItemController::labeling`)

**Критерий успеха при скане:**

1. Найти экземпляр по `storage_barcode` (статус 11 или 13)
2. Взять его `marketplace_item_id` (артикул)
3. Подсчитать активных заказов `status=13` («На сборке») на этот артикул
4. Вычесть уже отсканированные экземпляры этого артикула в текущей сессии
5. Если остаток > 0 → успех (звук success), иначе → «лишний» (звук error)

**Логика сканера (`PickupScan::handleScan()`):**

```
Сканирован ШК → поиск в MarketplaceOrderItem (storage_barcode, status IN [11,13])
  → Не найден → ошибка "Товар не найден на складе"
  → Найден → взять marketplace_item_id (артикул)
    → Подсчёт активных заказов (status=13) на этот артикул
    → Подсчёт уже отсканированных этого артикула в сессии
      → Нет активных заказов → ошибка "Нет активных заказов на «Бамбук»"
      → Отсканировано >= нужно → ошибка "Лишний! На «Бамбук» заказов: 5, уже отсканировано: 6"
      → Отсканировано < нужно → успех "Годен: Бамбук — 3/5 (полка: A1)"
```

**Счётчик «отсканировано/нужно» (напр. «3/5»):**

- Показывается в статус-сообщении успеха
- Живёт только в Livewire-сессии — перезагрузка страницы обнуляет его
- Учитывает только текущую сессию (не суммируется между кладовщиками)

**Управление отсканированными:**

- Таблица отсканированных экземпляров (только в сессии, без записи в БД)
- `removeScanned()` — убрать экземпляр из таблицы
- `clearAll()` — очистить весь список

**Навигация:**

- Точка входа: **кнопка «Сканер подбора»** (`btn-success`, иконка `fa-barcode`)
  на странице `/warehouse_of_item/to_pick_list` — сразу после кнопки «Печать
  списка»
- Кнопка «Назад» на странице сканера (`btn-outline-secondary`,
  `fa-arrow-left`) → ведёт на `warehouse_of_item.to_pick_list`
- Доступ: администраторы и кладовщики (`isAdmin() || isStorekeeper()`)

**Альтернативный путь подбора:**

- Текущий процесс: `/warehouse_of_item/to_pick_list` → `/to_pick/{order}` (скан
  по конкретному заказу)
- Новый сканер: упрощённый путь «у полки» без привязки к конкретному заказу

**Файлы:**

- `app/Livewire/PickupScan.php` — Livewire-компонент, метод `handleScan()`
- `resources/views/livewire/pickup-scan.blade.php` — input-сканер + таблица +
  audio success/error
- `resources/views/warehouse_of_item/pickup_scan.blade.php` — обёрточная view с
  кнопкой «Назад»
- `app/Http/Controllers/WarehouseOfItemController.php` — метод `pickupScan()` (
  тонкая обёртка)
- `routes/warehouse_of_item.php` — маршрут `GET /warehouse_of_item/pickup_scan`,
  name `warehouse_of_item.pickup_scan`, gate `can('viewAny', Shelf::class)`
- `resources/views/warehouse_of_item/to_pick_list.blade.php` — кнопка «Сканер
  подбора» (точка входа)

**Эталон паттерна:** `app/Livewire/BoxOrderScanner.php` (скан ШК в короб
FBO-поставки) — ✨ **НОВОЕ:** изменена сортировка заказов: последний
отсканированный
всегда сверху, заказы с тем же товаром (`marketplace_item_id`) подтягиваются за
ним, порядок персистентен (поле `marketplace_orders.boxed_at`). Обоснование:
легко увидеть, какие товары только что добавлены, и найти все экземпляры того же
товара рядом.

### Киоск (KioskService)

Интерфейс быстрого доступа для сотрудников:

- Проверка статуса заказа по роли (швея, закройщик)
- **Управление упаковочными материалами (флаер, пакет, флаер-пакет):**
  - Проверка наличия упаковки в цехе происходит ТОЛЬКО через этот поток
  - Упаковщик списывает упаковочные материалы при взятии товара на упаковку
  - В общем потоке взятия заказа (
    `MarketplaceOrderItemService::hasMaterialsInWorkshop`)
    упаковочные материалы (`Material::TYPE_PACKAGING`) исключены из проверки —
    они
    не нужны швеям и закройщикам
  - **Полная матрица «роль × тип материала» в hasMaterialsInWorkshop:**
    | Тип материала | Константа Material | Когда проверяется |
    |---|---|---|
    | Ткань (Тюль) | TYPE_FABRIC = 1 | Проверяется у закройщиков и швей с
    кроём; ПРОПУСКАЕТСЯ для швей без кроя (`seamstressNotCut`) |
    | Аксессуары (тесьма и т.п.) | TYPE_ACCESSORY = 2 | Проверяется ТОЛЬКО у
    швей (`isSeamstress()`); ПРОПУСКАЕТСЯ для закройщиков |
    | Упаковка | TYPE_PACKAGING = 3 | ПРОПУСКАЕТСЯ всегда в этом методе |
  - **Принцип:** материал проверяется у той роли, которая с ним физически
    работает
  - **Списание упаковки (все три точки):**
      - `MarketplaceOrderItemController::done()` — упаковка при
        стикеровке/упаковке
        (эталон правильного списания)
      - `StickerPrintingController::processRepack()` — переупаковка товара в ОТК
      - `StickerPrintingController::processReplace()` — подмена товара
      - `KioskService::hasPackagingMaterials()` — проверка наличия рулона
        упаковки в
        текущей смене (`Roll::STATUS_IN_WORKSHOP`, `shift_id = $shift->id`)
      - `KioskService::deductPackagingMaterials()` — списание с рулона (создаёт
        Order
        с `shift_id`/`workshop_id` и MovementMaterial с `roll_id`)
      - При отсутствии рулона или закрытой смене → `RuntimeException`
        (транзакция откатывается, ошибка логируется в канал `materials`)
- Авторизация ОТК для проверки
- Фильтрация товаров для стикеровки по ролям
- **Уборщицы и водители** имеют доступ в киоск любого цеха, но только для
  открытия/закрытия смены, без операционного функционала

### Сборка поставок (SupplyBoxController)

**Процесс сборки поставки:**

1. Создаются короба (`SupplyBox`) для распределения заказов
2. Заказы распределяются по коробам через `box_id`
3. Короба закрываются через `closed_at`
4. Поставка помечается как собранная (статус 4)

**Условия для завершения сборки (`markAssembled`):**

- Все короба закрыты (`closed_at` заполнен)
- Все заказы распределены (нет заказов с `box_id = null`)
- Поставка переходит в статус 4 (в отгрузке)
- Все заказы в поставке получают статус 3 (готов к отправке)

**TG+MAX-уведомления при сборке:**

- При успешной сборке отправляется уведомление админу и всем менеджерам с
  привязанным Telegram или MAX (параллельно)
- Используются `SendTelegramMessageJob` и `SendMaxMessageJob` с задержкой для
  менеджеров (чтобы избежать rate limits)
- Формат: `✅ Поставка #12345 (OZON) собрана.`

## Ключевые файлы

- `app/Services/InventoryService.php` — 12 методов инвентаризации, включая
  группировку материалов по типам; `materialsQuantityByWorkshopPerShift()`
  добавлен `type_id` в SELECT, groupBy и объект material
- `app/Services/WarehouseOfItemService.php` — управление складом товаров
- `app/Http/Controllers/InventoryController.php` — управление инвентаризацией и
  группировка материалов по типам; метод `byWorkshop()` теперь передаёт
  `sections`
  через `groupMaterialsByType()` (переиспользуемый метод для склада и цеха)
- `app/Services/StickerService.php` — генерация стикеров
- `app/Models/Shelf.php` — стеллажи
- `app/Models/ProductSticker.php` — данные стикеров
- `app/Models/InventoryCheck.php` / `InventoryCheckItem.php` — инвентаризация
- `app/Http/Controllers/BarcodeSearchController.php` — поиск по штрихкоду
- `app/Http/Controllers/SupplyBoxController.php` — управление коробами поставок
  и сборкой
- `app/Http/Controllers/MovementMaterialToWorkshopController.php` — создание
  запроса материалов (фильтрация по цеху)
- `app/Services/MovementMaterialToWorkshopService.php` — проверка доступности
  материала цеху
- `app/Http/Controllers/StickerPrintingController.php` — страница печати
  стикеров
  (фильтры материалов/размеров из БД)
- `app/Services/KioskService.php` — интерфейс киоска
- `app/Services/AutoOrderService.php` — автоматическое пополнение (фильтрация по
  цеху)
- `app/Jobs/SendTelegramMessageJob.php` — отправка TG-уведомлений при сборке
- `app/Jobs/SendMaxMessageJob.php` — отправка MAX-уведомлений при сборке (
  параллельно)
- `app/Livewire/PickupScan.php` — сканер подбора товаров (метод `handleScan()`,
  счётчик «отсканировано/нужно»)
- `app/Http/Controllers/WarehouseOfItemController.php` — метод `pickupScan()` —
  обёртка для сканера подбора, метод `toPickList()` — фильтрация по
  storage_barcode

## Бизнес-правила

- Штрихкоды хранения используют алгоритм Луна для защиты от ошибок ввода
- Товары со статусом 11 (на складе) и 13 (готов к отправке) участвуют в
  инвентаризации
- Стикеры генерируются с учётом особенностей маркетплейса (Ozon vs WB)
- Киоск ограничивает доступ по ролям
- **Сканер подбора:** экземпляры одного артикула (`marketplace_item_id`)
  взаимозаменяемы
  для заказов в статусе 13 («На сборке»). Кладовщик сканирует любой экземпляр
  артикула со склада (status 11/13), система проверяет, есть ли ещё
  неподобранные
  заказы этого артикула. Счётчик отсканированного живёт только в сессии.

## Связанные topics

- [material-flow.md](material-flow.md) — поступление и движение материалов
- [materials.md](materials.md) — материалы и их свойства (включая порог закрытия
  рулонов)
- [order-lifecycle.md](order-lifecycle.md) — статусы заказов
- [marketplace-integration.md](marketplace-integration.md) — штрихкоды
  маркетплейсов
- [shift-system.md](shift-system.md) — доступ к киосу по ролям
- [user-management.md](user-management.md) — роли и доступ пользователей
