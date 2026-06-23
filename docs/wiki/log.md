## [2026-06-23] update | auto-reset-cluster-priority

- Добавлен авто-сброс цеховой настройки `orders_cluster_priority` при исчерпании
  очереди заказов приоритетного FBO-кластера.
- Триггер: перевод заказа в стикеровку (status=5) в
  `MarketplaceOrderItemController::labeling()` и `WarehouseOfItemController`.
- Логика: метод `MarketplaceOrderItemService::resetClusterPriorityIfExhausted()`
  считает заказы кластера в статусах [0,4,7,8] (в этом цехе ИЛИ новые с
  workshop_id=NULL).
- Сбрасывает только цеховые настройки, глобальную не трогает. Логирует в канал
  `system`.
- 5 новых тестов в `MarketplaceOrderItemServiceTest`.
- Обновлены topics: order-lifecycle.md, marketplace-integration.md

## [2026-06-22] update | orders-cluster-priority

- Добавлена цеховая/глобальная настройка `orders_cluster_priority` (значения:
  `<marketplace_id>|<cluster>`, напр. `1|Казань`, `2|Коледино`, дефолт пусто) —
  приоритизация выдачи новых заказов швеям по FBO-кластеру
- Поле `marketplace_orders.cluster` (varchar, nullable) есть только у
  FBO-заказов;
  у FBS = null. Заполняется при синхронизации поставок из справочника
  `marketplace_warehouses.cluster`
- Кластеры у OZON (marketplace_id=1) и WB (marketplace_id=2) — РАЗНЫЕ наборы
  значений. Сравнение по ОБЕИМ полям (marketplace_id + cluster) — защита от
  коллизий имён кластеров
- Настройка ОРТОГОНАЛЬНА `orders_priority` (сортировка по маркетплейсу) и
  `orders_filter` (фильтр FBO/FBS). Применяется ПЕРВЫМ в цепочке сортировки (
  CASE
  WHEN), главнее `orders_priority`
- Поведение: ПРИОРИТИЗАЦИЯ (не фильтр). Заказы выбранного кластера идут ПЕРВЫМИ,
  остальные — после, но тоже выдаются
- Реализация в `MarketplaceOrderItemService::getFilteredItems()`:
  `orderByRaw('CASE WHEN marketplace_orders.marketplace_id = ? AND
  marketplace_orders.cluster = ? THEN 0 ELSE 1 END', [...])` — вставлен ПЕРВЫМ в
  цепочке сортировки (перед fulfillment_type, orders_priority, created_at)
- Источник опций для select: новый статический метод
  `MarketplaceWarehouse::clusterOptions()` — возвращает `[value => label]` вида
  `['1|Казань' => 'OZON — Казань', ...]` из таблицы `marketplace_warehouses`
  (distinct по marketplace_id+cluster)
- UI: и глобальные настройки (`resources/views/settings/index.blade.php`, select
  рендерится только если clusterOptions не пуст), и цеховые
  (`resources/views/workshops/edit.blade.php` через
  `WorkshopController::getSettingLabels/getSettingOptions`). Label =
  "Приоритетный FBO-кластер"
- Валидация: `app/Http/Requests/SaveSettingRequest.php` (
  `orders_cluster_priority`
  => sometimes|nullable|string)
- Дефолт: `database/seeders/SettingsSeeder.php` (пусто = выключено)
- Тесты: `tests/Feature/MarketplaceOrderItemServiceTest.php` — 3 теста на
  getFilteredItems (приоритизация, regression без настройки, FBS после
  приоритетного
  FBO)
- Обновлены topics: order-lifecycle.md, shift-system.md

## [2026-06-25] fix | marketplace-warehouses-clusters

- Фикс бага с кластерами OZON/WB в контексте фичи orders_cluster_priority
- **Ключевое бизнес-правило:** в таблице `marketplace_warehouses` структура
  данных
  РАЗНАЯ для маркетплейсов: OZON (marketplace_id=1) — поле `cluster` =
  город-группировка
  (Казань, Краснодар), много складов мапятся на 1 кластер. WB (
  marketplace_id=2) —
  поле `cluster` пустое, кластером служит `name` (склад/город). Соответственно,
  "кластерное значение" = OZON→cluster, WB→name
- `MarketplaceWarehouse::clustersByMarketplace(int $marketplaceId)` — новый
  метод,
  возвращает distinct кластерные значения: для mp=1 по полю `cluster`, для mp=2
  по полю `name`
- `MarketplaceWarehouse::clusterOptions()` — переделан, теперь использует
  clustersByMarketplace
  для обоих маркетплейсов (раньше фильтровал whereNotNull('cluster') и WB
  отсекался)
- `ExcelOrderImport::mount()` — select складов теперь строится через
  clustersByMarketplace.
  Для OZON в select теперь города-кластеры (раньше были конкретные склады name),
  для WB — склады (name)
- При импорте заказов поле `marketplace_orders.cluster` заполняется корректным
  кластерным
  значением (OZON=город, WB=склад), что обеспечивает работу кластерной
  приоритизации
  (`orders_cluster_priority`) в getFilteredItems
- Тесты: `tests/Feature/MarketplaceOrderItemServiceTest.php` — 2 новых теста на
  clustersByMarketplace/clusterOptions
- Обновлены topics: order-lifecycle.md, marketplace-integration.md

## [2026-06-24] update | fbo-order-detachment-third-button

- Добавлена ТРЕТЬЯ кнопка "Убрать на поставку" для массовой отвязки заказов
  в статусе 6 ("На поставку") от FBO-поставок: отвязка supply_id=null
  (без удаления), только заказы без короба (box_id IS NULL)
- `MarketplaceOrderService::detachOnSupplyOrdersBySupply()` — новый метод
- `MarketplaceOrderController::detachOnSupplyBySupply()` — обработчик
- Новый роут `DELETE /megatulle/marketplace_orders/detach-on-supply-by-supply/`
- Кнопка голубая (btn-info), доступна только admin при `$supply->status === 13`
- **Всего три массовых действия на странице FBO-поставки:**
    1. "Удалить все новые" (status=0) — полное удаление
    2. "Убрать не готовые" (status=4) — отвязка заказов без короба
    3. "Убрать на поставку" (status=6) — отвязка заказов без короба ✨ **НОВОЕ**
- Логирование в канал `orders` при отвязке
- Обновлены topics: order-lifecycle.md, marketplace-integration.md, finance.md

## [2026-06-17] add-role | cleaner (Уборщица)

- Добавлена новая роль пользователя `cleaner` (ID=8) с минимальным доступом
- Доступ: только авторизация + базовый дашборд, не входит ни в один Gate
- Не участвует в сменной системе (не добавлена в ShiftService::SHIFT_ROLES)
- **Обновлены topics:** user-management.md, shift-system.md
- Файлы изменений: RoleSeeder.php, User.php, UserService.php, create.blade.php,
  RoleFactory.php, UserTest.php

## [2026-06-20] feature | driver access to kiosk (any workshop, shift-only — same as cleaner)

- Водитель (driver) получил такой же ограниченный доступ в киоск любого цеха,
  как cleaner
- `StickerPrintingController::canAccessWorkshop()`: добавлен
  `|| $user->isDriver()`
- В киоске для driver видна только кнопка "Открытие / Закрытие смены", весь
  операционный функционал скрыт
- Водитель не привязан к цеху (`currentWorkshop() = null`), но может
  открывать/закрывать смену в любом цехе
- Итоговый список ролей с доступом в киоск любого цеха (canAccessWorkshop):
    - admin, storekeeper — полный функционал киоска
    - cleaner, driver — только "Открытие/Закрытие смены" (учёт времени), без
      привязки к цеху
- Обновлены topics: shift-system.md, user-management.md, warehouse-operations.md
- Файлы изменений: StickerPrintingController.php,
  resources/views/kiosk/kiosk.blade.php,
  tests/Feature/KioskLimitedAccessTest.php (переименован из
  KioskCleanerAccessTest)

## [2026-06-17] feature | cleaner access to kiosk (any workshop, shift-only, no workshop binding)

- Уборщица (cleaner) получила доступ в киоск любого цеха через
  `StickerPrintingController::canAccessWorkshop()`: добавлен
  `|| $user->isCleaner()`
- В киоске для cleaner скрыт весь операциональный функционал (печать заказов,
  статистика, работа с рулонами, браком, возвратами, стикерами)
- В виден только пункт "Открытие / Закрытие смены" для учёта рабочего времени
- Уборщица не привязана к сменному графику (`ShiftService::SHIFT_ROLES`), но
  `canWorkToday(cleaner) = true`
- Middleware RequireOpenShift пропускает cleaner после открытия смены
- Обновлены topics: shift-system.md, user-management.md, warehouse-operations.md
- Файлы изменений: StickerPrintingController.php,
  resources/views/kiosk/kiosk.blade.php,
  tests/Feature/KioskCleanerAccessTest.php

## [2026-06-17] update | salary-system

- Новое правило для cleaner/driver: дневной окład начисляется ТОЛЬКО если
  сотрудник
  в день начисления открыл смену И закрыл её. Источник правды:
  `schedules.shift_*_time` (не `users.shift_*`, которые обнуляются nightly
  cron).
  Проверка:
  `if (in_array($user->role?->name, self::REQUIRES_CLOSED_SHIFT_ROLES) && ($schedule->shift_opened_time === '00:00:00' || $schedule->shift_closed_time === '00:00:00'))`.
- Добавлена константа `REQUIRES_CLOSED_SHIFT_ROLES = ['cleaner', 'driver']` в
  ActionAccrualService
- Остальные роли (seamstress, cutter, otk, storekeeper, manager, admin) —
  прежняя логика (порог действий / безусловное начисление)
- **Технический нюанс:** cron `accrual:salary-daily` запускается в 00:30, читает
  `Schedule::yesterday()`, где `shift_*_time` сохранены (не обнулены)
- **Связь с shift-system.md:** cleaner/driver не участвуют в сменном графике, но
  имеют доступ в киоск для учёта рабочего времени → логика оклада привязана к их
  фактическому присутствию
- Обновлены topics: salary-system.md, shift-system.md, user-management.md
- Файлы изменений: `app/Services/ActionAccrualService.php` (константа + guard),
  `tests/Feature/Services/ActionAccrualServiceTest.php`

## [2026-06-17] update | roll-closure-threshold

- Бизнес-правило "минимальный остаток для закрытия рулона" перенесено из
  глобальной настройки `roll_close_min_remaining` в поле материала
  `minimum_roll_size_for_closure` (decimal(8,2), NOT NULL, default 10.00)
- Теперь у каждого материала свой порог закрытия рулона вместо единого для всех
- Киоск (`/kiosk/rolls`): рядовой сотрудник может завершить рулон только если
  `current_quantity <= material.minimum_roll_size_for_closure`, иначе alert
  "Рулон еще не заканчивается!" (доступен кладовщикам/админам)
- Проверка порога только в шаблоне kiosk/rolls.blade.php:89 (серверная проверка
  completeRoll() отсутствует)
- Обновлены topics: material-flow.md, shift-system.md, создан materials.md
- Удалена настройка roll_close_min_remaining из settings/seeders/Controllers

## [2026-06-18] fix | accessory-check-only-for-seamstress

- `Material::TYPE_ACCESSORY = 2` (НОВАЯ константа) — аксессуары (тесьма, шнурки
  и
  т.п.)
- `MarketplaceOrderItemService::hasMaterialsInWorkshop()`: добавлено правило для
  аксессуаров — проверяются ТОЛЬКО у швей (`isSeamstress()`), пропускаются для
  закройщиков
- **Полная матрица «роль × тип материала» в hasMaterialsInWorkshop:**
  | Тип материала | Константа | Когда проверяется |
  |---|---|---|
  | Ткань (Тюль) | TYPE_FABRIC = 1 | У закройщиков и швей с кроём; ПРОПУСКАЕТСЯ
  для швей без кроя (`seamstressNotCut`) |
  | Аксессуары (тесьма и т.п.) | TYPE_ACCESSORY = 2 (НОВАЯ) | ТОЛЬКО у швей
  (`isSeamstress()`); ПРОПУСКАЕТСЯ для закройщиков |
  | Упаковка | TYPE_PACKAGING = 3 | ПРОПУСКАЕТСЯ всегда; упаковка — отдельный
  поток упаковщика |
- **Принцип:** материал проверяется у той роли, которая с ним физически работает
  (ткань — у кроящего, аксессуары — у шьющей швеи, упаковка — у упаковщика)
- Обновлены topics: order-lifecycle.md, warehouse-operations.md, materials.md,
  material-flow.md

## [2026-06-18] fix | packaging-check-excluded-from-workshop-availability

- `MarketplaceOrderItemService::hasMaterialsInWorkshop()`: упаковочные материалы
  (`Material::TYPE_PACKAGING`) исключены из проверки наличия в цехе при взятии
  заказа швеёй/закройщиком
- Обоснование: упаковка списывается ОТДЕЛЬНЫМ потоком упаковщика через
  `KioskService` и `StickerPrintingController`; швеи и закройщики не используют
  упаковку — предыдущая проверка была багом
- Ткани (`Material::TYPE_FABRIC`) теперь проверяются через константу вместо
  магического числа `type_id == 1`
- Швеи без кроя (`seamstressNotCut`) пропускают проверку тканей — крой делает
  закройщик
- Обновлены topics: order-lifecycle.md, warehouse-operations.md, materials.md,
  material-flow.md

## [2026-06-19] feature | material-supplier-shortage-tracking

- Новая pivot-таблица `material_supplier` (material_id, supplier_id,
  shortage_percent decimal 5,2) — связь многие-ко-многим между материалами и
  поставщиками
- Новые отношения: `Material::suppliers()` и `Supplier::materials()` с pivot-
  полями id и shortage_percent
- Новый бизнес-функционал: учёт процента недосдачи по каждому поставщику для
  каждого материала (справочно, пока не применяется в расчётах)
- Новый контроллер `MaterialSupplierController` с методами:
  - `attach()` — привязать поставщика к материалу (POST
    /materials/{material}/suppliers)
  - `updateShortages()` — массовое обновление процента недосдачи (PUT
    /materials/{material}/suppliers)
  - `detach()` — отвязать поставщика от материала (DELETE
    /materials/{material}/suppliers/{pivotId})
- UI: карточка «Поставщики» в `resources/views/materials/edit.blade.php` с
  таблицей
  поставщиков и формами управления
- Логирование операций в канал `materials` (привязка/обновление/отвязка)
- Обновлены topics: materials.md, material-flow.md, INDEX.md
