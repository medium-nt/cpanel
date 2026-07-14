## [2026-07-14] fix | rating-board-empty-data-behavior

- Исправлено описание поведения когда все лидеры закрыли смену: раньше было
  указано CSS-скрытие через `.all-shift-done` и `toggleAllShiftDone()`, но
  актуальная реализация — через `normalizeData(data)` (возвращает пустые
  `leaders: []` и `podium: { gold: null, silver: null, bronze: null }`).
- Эффект: таблица лидеров показывает только заголовок (без карточек), подиум —
  3 empty-слота (`top__item--empty`). Блоки остаются на экране, НЕ скрываются.
- Подход через нормализацию данных (НЕ через CSS) обеспечивает согласованность
  `lastData` с DOM для корректной работы diff-логики.
- Обновлён topic: rating-board.md

## [2026-06-27] fix | shift-system-current-users-count

- Фикс задвоения счётчиков `users_count` на страницах списков смен/цехов —
  переведённые сотрудники учитывались в ОБЕИХ сменах через `withCount('users')`.
- Добавлен relation `Shift::currentUsers(): BelongsToMany` — фильтрует pivot по
  `effective_from <= today` и исключает переведённых через `whereNotExists` (
  есть более свежая `effective_from` в другой смене).
- Счётчики теперь используют `withCount(['currentUsers as users_count'])` в
  `ShiftController::index()` и `WorkshopController::index()/edit()`.
- Корреляция в `whereNotExists` через
  `whereColumn('su2.shift_id', '!=', 'shift_user.shift_id')` — НЕ через
  `$this->id` (иначе withCount ломается на donor-instance без id).
- Метод `getCurrentUsers()` теперь делегирует в
  `currentUsers()->get()->unique('id')`.
- Тесты: `tests/Feature/ShiftCurrentUsersTest.php` — 4 теста на счётчики при
  переводе.
- Обновлён topic: shift-system.md

## [2026-06-23] fix | warehouse-pickup-scanner-navigation

- Изменена точка входа на сканер подбора: плитка «Сканер подбора» УБРАНА с
  дашборда (`home.blade.php`)
- Кнопка «Сканер подбора» (`btn-success`, `fa-barcode`) теперь на странице
  `/warehouse_of_item/to_pick_list` — сразу после кнопки «Печать списка»
- Добавлена кнопка «Назад» (`btn-outline-secondary`, `fa-arrow-left`) на
  странице сканера → ведёт на `warehouse_of_item.to_pick_list`
- Обновлены topics: warehouse-operations.md, order-lifecycle.md

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

## [2026-06-29] update | stage2-notification-service

- Завершён Этап 2 миграции Telegram→MAX — массовые рассылки переведены на
  NotificationService.
- `app/Services/NotificationService.php` — единый шлюз уведомлений (sync/queued,
  delay).
- `UserService::getListSeamstressesWorkingToday()` /
  `getListStorekeepersWorkingToday()`
  / `getListManagersWithTg()` — теперь возвращают `Collection<User>`, фильтр
  изменён с `where tg_id != null` на
  `where(fn $q => $q->whereNotNull('tg_id')->orWhereNotNull('max_id'))`
  (сотрудник попадает если привязан ХОТЯ БЫ один мессенджер).
- 14 точек массовой рассылки переведены с `foreach ($tgIds as $tgId) {
  TgService/SendTelegramMessageJob }` на `foreach ($users as $user) {
  NotificationService::notify($user, $text[, queued: true, delaySeconds: $i]) }`.
  Файлы: DefectMaterialService, AutoOrderService, MarketplaceApiService,
  MovementMaterialToWorkshopService, MovementDefectMaterialToSupplierService,
  WorkshopRollScan, DefectMaterialScan, MovementMaterialToWorkshopController,
  StickerPrintingController, SupplyBoxController.
- Тест: `tests/Feature/Services/NotificationServiceTest.php` (5 тестов).
- Обновлены topics: user-management.md, shift-system.md, material-flow.md,
  order-lifecycle.md

## [2026-06-30] update | support-system-status-in-progress

- Добавлен 4-й статус `in_progress` («В работе») — переход new → in_progress (
  start)
- Новое поле `tickets.admin_comment` (text, nullable) — комментарий
  администратора при закрытии («что сделано»)
- Новый роут `PUT /tickets/{ticket}/start` → `tickets.start` (только админ,
  только из new)
- Обновлён жизненный цикл: `new ──start──▶ in_progress ──close──▶ closed` +
  крестовые переходы в deleted
- Scope `opened()` модели теперь возвращает
  `whereIn('status', [new, in_progress])` — в табе «Новые»
- Бейдж меню считает только `STATUS_NEW` (in_progress уже в работе, не требует
  внимания)
- Policy обновлена: start (new), close (new + in_progress), delete (new +
  in_progress)
- UI: кнопки по статусам в show.blade.php, блок комментария админа, форма
  закрытия с textarea admin_comment
- Тесты: 7 новых тестов в TicketTest (всего 22), state `inProgress()` в
  TicketFactory
- Обновлён topic: support-system.md

## [2026-06-30] update | support-system-close-rules

- Уточнены правила закрытия тикетов: закрыть можно ТОЛЬКО из статуса
  `in_progress` (НЕЛЬЗЯ напрямую из `new`).
- `TicketPolicy::close`: сужен с `[new, in_progress]` до `in_progress` только.
- `TicketService::close(string $adminComment)`: обязательный комментарий (ранее
  `?string`), guard на `trim($adminComment) !== ''`.
- Валидация в `TicketController::close`: `admin_comment` required (ранее
  nullable).
- Lifecycle теперь строго:
  `new → start → in_progress → close (с обяз. комментарием) → closed`.
- Обновлён topic: support-system.md

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
- Новый бизнес-функционал: учёт процента недостачи по каждому поставщику для
  каждого материала (справочно, пока не применяется в расчётах)
- Новый контроллер `MaterialSupplierController` с методами:
  - `attach()` — привязать поставщика к материалу (POST
    /materials/{material}/suppliers)
  - `updateShortages()` — массовое обновление процента недостачи (PUT
    /materials/{material}/suppliers)
  - `detach()` — отвязать поставщика от материала (DELETE
    /materials/{material}/suppliers/{pivotId})
- UI: карточка «Поставщики» в `resources/views/materials/edit.blade.php` с
  таблицей
  поставщиков и формами управления
- Логирование операций в канал `materials` (привязка/обновление/отвязка)
- Обновлены topics: materials.md, material-flow.md, INDEX.md

## [2026-06-25] fix | kiosk-packaging-roll-deduction

- Исправлен баг списания упаковочных материалов в киоске при переупаковке (
  repack)
  и подмене товара (replace). Раньше упаковка списывалась «из ниоткуда» через
  `InventoryService::materialInWorkshop` без привязки к смене и рулону.
- Теперь списание упаковки унифицировано для всех трёх точек:
  стикеровка/упаковка
  (`MarketplaceOrderItemController::done()` — эталон), переупаковка
  (`StickerPrintingController::processRepack()`), подмена
  (`StickerPrintingController::processReplace()`).
- **Ключевые изменения:**
    - `KioskService::hasPackagingMaterials()` — проверяет наличие рулона
      упаковки в
      текущей смене (`Roll::STATUS_IN_WORKSHOP`, `shift_id = $shift->id`)
    - `KioskService::deductPackagingMaterials()` — создаёт Order с
      `shift_id`/`workshop_id` и MovementMaterial с `roll_id`
    - При отсутствии рулона или закрытой смене → `RuntimeException` (транзакция
      откатывается, ошибка логируется в канал `materials`)
- **Бизнес-правило:** рулоны упаковки привязаны к смене (`shift_id`), НЕ к цеху.
  В цехе на смену — один рулон упаковки (ограничение также в
  `WorkshopRollScan`).
- Файлы изменений: `app/Services/KioskService.php`,
  `app/Http/Controllers/StickerPrintingController.php`,
  `tests/Feature/Services/KioskServiceTest.php`,
  `tests/Feature/Controllers/ProcessRepackTest.php` (новый).
- Обновлены topics: material-flow.md, materials.md, warehouse-operations.md,
  order-lifecycle.md

## [2026-06-25] update | fbo-supply-date-editable

- В форме редактирования FBO-поставки (
  `/megatulle/marketplace_supplies/{id}/edit-fbo`)
  поле `supply_date` («Дата поставки в МП») теперь редактируется вручную
  пользователем (раньше редактировались только gazelka-поля).
- Проверка валидации дат в `MarketplaceSupplyController::updateFbo()`
  перестроена:
  теперь она опирается на НОВЫЕ значения обоих полей (gazelka_shipment_date и
  supply_date), а не на старое supply_date из БД.
- Фронтенд-валидация: input `supply_date` имеет атрибут `min = gazelka_shipment_date + 1
  день`.
- Обновлён topic: marketplace-integration.md

## [2026-06-25] fix | fbo-mark-shipped-button-status-condition

- Исправлено условие показа кнопки «Поставка отгружена» в OZON FBO
  (`show-ozon-fbo.blade.php:237`): было `status !== 3` (баг — кнопка была видна
  при 0, 4, 13), стало `status == 4` (только при статусе «Отгрузка»).
- Жизненный цикл FBO: `0 (Открытая) → 13 (Сформирована) → 4 (Отгрузка) → 3
  (Закрытая)`
- **Различие реализации:**
    - **OZON FBO:** явное условие `status == 4` (стикер опционален)
    - **WB FBO:** косвенная завязка через стикер — `$supply->sticker && status !==
      3` (стикер обязателен, загружается только при status=4)
- **Технический нюанс:** контроллер `markShipped` НЕ имеет guard на текущий
  статус (проверяет только status===3 от повторной отгрузки). Прямой вызов
  роута для поставки в status 0/13 технически возможен — известная дыра,
  сознательно пока не закрыта (фикс был только UI).
- Тест: `tests/Feature/Controllers/MarketplaceSupplyControllerTest.php` —
  проверка что status=13 кнопка скрыта, status=4 видна.
- Обновлён topic: marketplace-integration.md

## [2026-06-25] feature | fbo-supply-unmark-shipped

- Добавлен механизм отката отгрузки FBO-поставки: метод
  `MarketplaceSupplyController::unmarkShipped()`
  позволяет администратору вернуть поставку из статуса 3 (Закрытая/Отгружена)
  обратно в статус 4 (Отгрузка),
  чтобы снова стало доступно редактирование (стикер, накладная, состав).
- Меняет ТОЛЬКО `marketplace_supplies.status`: 3 → 4. `completed_at` НЕ
  трогается (сохраняется значение из markShipped).
- НЕ затрагивает заказы (marketplace_orders уже в status=3), короба, остатки,
  историю.
- Доступ: только админ (policy `unmarkShipped` → isAdmin()). Guard: если
  `status !== 3` → redirect back с error «Поставка не отгружена.»
- UI: кнопка «Отменить отгрузку» на страницах show-wb-fbo.blade.php и
  show-ozon-fbo.blade.php при `status===3 && admin`.
- Лог: `Log::channel('marketplace_supplies')->notice(...)` — «отменил отгрузку
  поставки #ID».
- Поток статусов FBO:
  `0 (Открытая) → 13 (Сформирована) → 4 (Отгрузка) → 3 (Закрытая)` добавлена
  обратная стрелка `3 → 4` через `unmarkShipped` (admin only).
- Тесты: `tests/Feature/MarketplaceSupplyUnmarkShippedTest.php` — 3 pest-теста.
- Обновлены topics: marketplace-integration.md

## [2026-06-25] update | max_fabric_rolls_per_shift в настройках

- Лимит «максимум рулонов ткани одного вида на смену в цехе» перенесён из
  захардкоженной константы `Material::MAX_FABRIC_ROLLS_PER_SHIFT = 99` в
  настройки
  системы (25.06.2026)
- Новая настройка `max_fabric_rolls_per_shift` со стандартной схемой settings:
  глобальное значение (по умолчанию 99) + возможность цехового переопределения
  через карточку цеха
- Чтение лимита в коде:
  `Setting::getValue('max_fabric_rolls_per_shift', $order->shift->workshop_id)` —
  встроенный fallback "цеховая → глобальная"
- Лимит применяется только к ткани (Material::TYPE_FABRIC)
- Проверка лимита в двух точках:
    1. `WorkshopRollScan::scanRoll()` — при сканировании рулонов в поставку
    2. `MovementMaterialToWorkshopController::save_receive()` — при финальной
       приёмке поставки
- Считаются рулоны в статусах `IN_WORKSHOP` + `SHIPPED_TO_WORKSHOP` с тем же
  `material_id` и `shift_id`
- Затронутые файлы:
    - `app/Models/Material.php` — удалена константа MAX_FABRIC_ROLLS_PER_SHIFT
    - `app/Livewire/WorkshopRollScan.php` — проверка лимита при сканировании
    - `app/Http/Controllers/MovementMaterialToWorkshopController.php` — проверка
      при
      финальной приёмке
    - `database/seeders/SettingsSeeder.php` — глобальный дефолт = 99
    - `app/Http/Controllers/WorkshopController.php` — лейбл для UI карточки цеха
    - `app/Http/Requests/SaveSettingRequest.php` — правило валидации
      (sometimes|integer|min:1)
    - `resources/views/settings/index.blade.php` — поле в глобальном UI настроек
- Обновлены topics: material-flow.md, materials.md, shift-system.md

## [2026-06-26] update | box-order-scanner-sorting

- Изменена логика отображения таблицы «Заказы в коробе» на странице сканирования
  `/megatulle/marketplace_supplies/{supply}/boxes/{box}` (Livewire-компонент
  `BoxOrderScanner`).
- **Было:** заказы выводились без явной сортировки (по `id` ASC) — последний
  добавленный уходил в низ.
- **Стало:** последний отсканированный заказ всегда в первой строке; все заказы
  с
  тем же товаром подтягиваются следом за ним; остальные — ниже. Порядок
  персистентен (сохраняется после перезагрузки страницы).
- **Реализация:**
    - Новое поле в БД: `marketplace_orders.boxed_at` (nullable timestamp) —
      время добавления заказа в короб. Миграция
      `2026_06_26_061618_add_boxed_at_to_marketplace_orders_table` с backfill
      `boxed_at = updated_at` для существующих заказов.
    - `BoxOrderScanner::handleScan()` проставляет `boxed_at = now()` при
      добавлении
      в короб.
    - `BoxOrderScanner::removeOrder()` сбрасывает `boxed_at = null` при отвязке.
    - `BoxOrderScanner::render()` сортирует заказы:
      `sortByDesc('boxed_at') → groupBy('marketplace_item_id') →
      sortByDesc(max('boxed_at') группы) → flatten()`. Группировка по
      `marketplace_item_id` (товар со своим размером; один заказ = один item
      согласно legacy-структуре — см. reference_one_order_one_item).
- **Затронутые файлы:**
    -
    `database/migrations/2026_06_26_061618_add_boxed_at_to_marketplace_orders_table.php`
    - `app/Models/MarketplaceOrder.php` (поле boxed_at, cast datetime, fillable)
    - `app/Livewire/BoxOrderScanner.php` (handleScan, removeOrder, render)
    - `resources/views/livewire/box-order-scanner.blade.php`
    - `tests/Feature/BoxOrderScannerTest.php`
- Обновлены topics: marketplace-integration.md, warehouse-operations.md

## [2026-06-28] add | support-system

- Создана новая тикет-система «Поддержка» для сотрудников报告 проблем
- Модель `Ticket` (таблица `tickets`) с полями: user_id, description, page_url,
  screenshot, status (new/closed/deleted), closed_at
- Сервис `TicketService`, контроллер `TicketController`, policy `TicketPolicy`,
  request `StoreTicketRequest`, factory `TicketFactory`
- Роуты в `routes/tickets.php` (префикс megatulle, middleware auth БЕЗ
  require_open_shift):
    - GET /megatulle/tickets — список с вкладками «Новые» (new) и
      «Обработанные» (closed+deleted)
    - GET /megatulle/tickets/create?url=... — форма создания с предзаполнением
      page_url
    - POST /megatulle/tickets — создание (валидация: description required/max:
      5000, page_url nullable/url/max:500, screenshot nullable/image/max:5120)
    - GET /megatulle/tickets/tickets/{ticket} — детальная страница
    - PUT /megatulle/tickets/tickets/{ticket}/close — закрыть (admin only,
      status=closed + closed_at)
    - PUT /megatulle/tickets/tickets/{ticket}/delete — в корзину (admin only,
      status=deleted)
- Авторизация: view (автор ИЛИ admin), create (любой), close/delete (admin only)
- Бейдж новых тикетов в меню: BuildingMenu listener в AppServiceProvider,
  показывается только admin, считает Ticket::new()->count()
- Кнопка создания в navbar: получает текущий URL через JavaScript (
  window.location.href)
- Blade views: index (вкладки), create (форма), show (детали)
- Жизненный цикл: new → closed (показ в «Обработанные») / deleted (корзина)
- Создан topic: support-system.md
- Обновлены topics: user-management.md (связь с ролями)

## [2026-06-28] fix | support-system

- Исправлены фактические ошибки в topic support-system.md после сверки с
  реальным кодом
- Жизненный цикл: НЕ линейный (new → deleted И closed → deleted через
  markDeleted), deleted — финальная корзина
- Scope для фильтрации new: метод называется `opened()` (НЕ `new` — слово
  зарезервировано в PHP), query-параметр `?scope=` (НЕ `?status=`)
- Роуты: одинарный `tickets` в путях (НЕ двойной `/tickets/tickets/`)
- Кнопка создания: серверный подход через `request()->fullUrl()` в
  `layouts/app.blade.php` (НЕ JavaScript и НЕ navbar.blade.php)
- Бейдж новых: логика remove + addAfter в AppServiceProvider, показывается
  только когда count>0 (НЕ всегда)
- Ключевые файлы: добавлены `config/adminlte.php`,
  `lang/vendor/adminlte/ru/menu.php`, исправлены пути
- Обновлён topic: support-system.md

## [2026-06-28] update | support-system

- Переименование: «Поддержка» → «Тикеты» (перевод в
  `lang/vendor/adminlte/ru/menu.php`: 'support' => 'Тикеты')
- Позиция в меню: самый низ — после «Настройки» (settings submenu), перед
  «Просмотр логов» (logs). BuildingMenu listener использует `addBefore('logs')`
  вместо `addAfter('main')`. Бейдж не уводит пункт наверх.
- Code-review улучшения: guard статусов в `TicketPolicy` (close = isAdmin +
  status new, delete = isAdmin + status НЕ deleted), дублирующий guard в
  `TicketService` (возвращает false при неверном статусе), логирование аудита
  через `Log::info` с ticket_id и admin_id
- N+1 устранён: `TicketController@index` использует `->with('user')` для eager
  load автора
- Полировка UI: счётчики в табах («Новые (N)» с badge-danger, «Обработанные (N)»
  с badge-secondary, скрыты при 0), заголовок страницы «Тикеты», проверка
  `Storage::exists()` для скриншота в `show.blade.php` (показ «Файл
  недоступен»), a11y (`aria-label` на кнопке navbar), мобильная версия (только
  иконка-жук)
- Upload скриншота: индикатор «Загрузка...» при выборе файла убран (фриз
  браузера из-за антивируса/ОС)
- Обновлён topic: support-system.md

## [2026-07-02] update | workshop-inventory-split-by-type

- На странице `/megatulle/inventory/workshop` («Материал на производстве»)
  материалы теперь разделены по типам (как на складе `/megatulle/inventory/`):
  каждая секция в отдельной card с заголовком и badge количества позиций
- `InventoryService::materialsQuantityByWorkshopPerShift()`: добавлен `type_id`
  в SELECT (строка 276), groupBy (строка 281) и в объект material (строка 291)
- `InventoryController::byWorkshop()`: теперь передаёт `sections` через
  `groupMaterialsByType()` вместо плоского `materials`; метод
  `groupMaterialsByType()`
  стал переиспользуемым (склад + цех)
- `resources/views/inventory/workshop.blade.php`: desktop-таблица и
  mobile-карточки
  обёрнуты в `@foreach ($sections as $section)` с card-header; обводка рабочих
  смен
  через CSS-классы `.today-shift-col(-top/-bottom)` сохранена
- Обновлён topic: warehouse-operations.md

## [2026-07-02] update | notifications-circuit-breaker

- Circuit Breaker в TgService/MaxService: `sendMessage()` теперь возвращает
  `bool`; HTTP 429 → cache-флаг на 30 мин, HTTP 403 → флаг на 6 ч (TG: любой
  403, MAX: только dialog.suspended)
- Jobs (`SendTelegramMessageJob`/`SendMaxMessageJob`): NOTICE «отправлено»
  только
  при `true`; при `false` — warning «пропущено (circuit breaker)». Retry при
  429/403 НЕ делается.
- Cooldown кейса «нет материала» (
  `MarketplaceOrderItemService::notifyNoMaterials`):
  блокировка отправки уведомления админу на 30 мин через Cache-флаг
  `no_material:item:{itemId}` → защита от спама при повторных кликах «Получить
  новый заказ»
- Логи CB-событий пишутся в каналы `tg`/`max` (уровни info/warning)
- Обновлены topics: user-management.md, marketplace-integration.md,
  logging-channels.md

## [2026-06-28] update | max-messenger-notifications

- Реализован ВТОРОЙ канал уведомлений — MAX мессенджер (параллельно с Telegram)
- Новая колонка `users.max_id` (varchar 255, nullable) — аналог tg_id для MAX
  chat_id
- Новые сервисы: `MaxService::sendMessage()` (клон TgService),
  `SendMaxMessageJob`
  (клон SendTelegramMessageJob)
- Webhook MAX: POST `/api/max/webhook` через `MaxController` (обрабатывает
  событие
  `message_created`, chat_id в `message.recipient.chat_id`)
- Привязка MAX: GET `/megatulle/users/profile?max_id=...` (аналог tg_id)
- Отключение MAX: роут `profile.disconnectMax`
- Команда подписки webhook: `max:subscribe-webhook` (регистрация в MAX API)
- Конфигурация: секция `max` в config/services.php (token,
  api_url=platform-api2.max.ru,
  admin_id, webhook_url); канал лога `max` в config/logging.php
- .env переменные: MAX_BOT_TOKEN, MAX_API_URL, MAX_ADMIN_ID, MAX_WEBHOOK_URL
- Этап 1 (РЕАЛИЗОВАН): дублирование всех admin-уведомлений (~13 файлов) и
  персональных
  по объекту User (UserService, MarketplaceOrderItemService). Стратегия
  «параллельно»:
  каждое уведомление уходит и в TG, и в MAX (у кого привязан chat_id)
- Технические нюансы MAX: Authorization RAW токен БЕЗ 'Bearer ' префикса (иначе
  401);
  API домен сменился platform-api.max.ru → platform-api2.max.ru (дедлайн
  19.07.2026);
  long polling /updates и webhook взаимоисключающи
- Этап 2 (НЕ реализовано): массовые рассылки через foreach (
  getListSeamstressesWorkingToday
  и др.) — хелперы возвращают Collection<tg_id>, для MAX нужна адаптация (~25
  точек)
- Обновлены topics: logging-channels.md (канал `max`), user-management.md (
  мессенджер-интеграции),
  marketplace-integration.md (TG+MAX-уведомления), warehouse-operations.md (
  TG+MAX при сборке),
  material-flow.md (TG+MAX уведомления), order-lifecycle.md (TG+MAX уведомления)

## [2026-07-02] update | materials-is_archive

- Введена двухфлаговая модель состояний материала: `is_active` (можно заказать)
  и `is_archive` (в архиве, скрыт из просмотров остатков)
- Новое поле `is_archive` (boolean, default false) в таблице materials; scope
  `Material::notArchived()` возвращает материалы с `is_archive=false`
- Фильтрация по `is_archive=false` применена во всех просмотрах остатков:
  `InventoryService::materialsQuantityByWarehouse()` и
  `materialsQuantityByWorkshopAggregate()` (строки 145, 176)
- UI: в `/materials/{id}/edit` — один select «Статус» с 3 опциями (Активен /
  Нельзя заказать / В архиве), маппится в (is_active, is_archive). В
  `/materials` — 3 цветных бейджа.
- Карта фильтрации: просмотры остатков → только `is_archive=false` (видны
  активные и «нельзя заказать»); формы заказа (Этап 2) → оба флага; админка →
  все материалы
- Обновлены topics: materials.md, warehouse-operations.md, material-flow.md

## [2026-07-02] update | materials-archive-phase2-forms-canarchive

- Реализован Этап 2 архивирования материалов: фильтрация в формах + защита
  перевода в архив
- Два scope в Material.php: `scopeActive` (is_active=true AND is_archive=false)
  для форм заказа/выбора, `scopeNotArchived` (is_archive=false) для форм
  списания/возврата
- Фильтрация ЗАКАЗ/ВЫБОР (`Material::active()`): movements_to_workshop/create,
  marketplace_items/create+edit, defect_materials/create,
  movements_from_supplier/create+edit, users/edit, workshops/edit,
  AutoOrderService
- Фильтрация СПИСАНИЕ/ВОЗВРАТ (`Material::notArchived()`):
  movements_defect_to_supplier/create, write_off_remnants/create, Livewire
  MaterialForm (mount)
- Новый метод `InventoryService::canArchive(Material)`: проверка возможности
  перевода в архив (условие: materialInWarehouse==0 AND materialInWorkshop==0)
- `MaterialController::update`: защита перевода в «Архив» — возможен только из
  «Нельзя заказать» (is_active=false) и при canArchive=true, иначе redirect back
  с ошибкой
- UI `materials/edit.blade.php`: option «В архиве» @disabled когда материал
  активный; alert для session('error')
- Бизнес-правило пути статусов: `Активен → Нельзя заказать → Архив`
- Обновлены topics: materials.md (добавлены scopeActive, canArchive, путь
  статусов, список контроллеров), material-flow.md (раздел фильтрации расширен),
  warehouse-operations.md (добавлен canArchive)

## [2026-07-04] create | max-integration
Создан topic max-integration.md: описание webhook, команд бота (/users), доступа по ролям, MaxService.

## [2026-07-08] update | warehouse-operations

- Добавлена ВТОРАЯ кнопка «Утилизировать все» на странице
  `/megatulle/warehouse_of_item/new_refunds` — массовая утилизация товаров со
  статусом 10 («На разборе» / «Переданные на осмотр в цех») в финальный статус
    17.
- Новые файлы: `WarehouseOfItemController::utilizeRefunds()`, POST-роут
  `warehouse_of_item.new_refunds.utilize_all`, кнопка в
  `new_refunds.blade.php` (admin only).
- Теперь три потока утилизации брака: стандартный (16→19→17 через сканер),
  массовая из цеха (16→17 на `/status_change_scan`), массовая «переданных на
  осмотр» (10→17 на `/new_refunds`).
- Обновлён topic: warehouse-operations.md

## [2026-07-08] update | warehouse-operations

- Добавлен раздел «Утилизация брака» — описание двух flow: стандартный через
  сканер
  (16→19→17) и массовая утилизация админом (16→17 напрямую через кнопку
  «Утилизировать все»).
- Новые файлы: `WarehouseOfItemController::utilizeDefects()` (ранее
  `utilizeAll`), POST-роут
  `warehouse_of_item.status_change_scan.utilize_defects`, кнопка в
  `status_change_scan.blade.php` (admin only).
- Обновлён topic: warehouse-operations.md

## [2026-07-08] refactor | warehouse-operations

- Переименование: `utilizeAll()` → `utilizeDefects()` (метод утилизирует только
  status 16, а не «вообще все»).
- Роут `warehouse_of_item.status_change_scan.utilize_all` → `.utilize_defects`,
  URL `/status_change_scan/utilize_all` → `/utilize_defects`.
- Тест-файл `WarehouseOfItemUtilizeAllTest.php` →
  `WarehouseOfItemUtilizeDefectsTest.php`.
- Ветка `utilizeRefunds` (status 10, new_refunds) не менялась.

## [2026-07-08] update | order-lifecycle

- Расширен контракт метода
  `MarketplaceOrderItemService::resetClusterPriorityIfExhausted()`
  — теперь принимает `?int $workshopId` (вместо `int`).
- При передаче `int` — поведение прежнее (проверяет конкретный цех).
- При передаче `null` (складской сценарий —
  `WarehouseOfItemController::labeling`)
  — обходит ВСЕ цехи с непустой настройкой `orders_cluster_priority` и
  сбрасывает
  истощённые.
- Реализация вынесена в приватный метод `resetClusterPriorityForWorkshop(int
  $workshopId)`.
- Фикс TypeError: раньше падал при `null` из складского сценария.
- Обновлён topic: order-lifecycle.md
- Обновлён map: services.md

## [2026-07-08] add | gazelka-api-service

- Создан `App\Services\GazelkaApiService` — API-клиент доставки Газелька (
  gazelka.space).
  Инстанс-класс с DI (НЕ статика, в отличие от MarketplaceApiService), 9
  публичных
  методов.
- Конфигурация: секция `gazelka` в config/services.php (token, base_url,
  timeout,
  verify_ssl=false по умолчанию для shared-хостинга Beget).
- Методы: descriptions() (справочник статусов+МП), schedule() (график по
  городу),
  newPlan() (создать заявку), deletePlan() (удалить заявку), myPlans() (мои
  заявки),
  createPickup() (забор груза), addToPickup() (добавить в забор),
  removeFromPickup() (убрать из забора), pricelist() (прайслист).
- Const-справочники: SUPPLY_TYPE_* (monomix 1-7), CITY_* (1-5), PAYMENT_* (1-3).
- Авторизация Bearer-токен из config('services.gazelka.token').
- Логи ошибок → канал `marketplace_supplies`.
- Тесты: tests/Feature/Services/GazelkaApiServiceTest.php (13 тестов, зелёные).
- Обновлён INDEX.md (Services: 27)
- Обновлены maps: services.md (методы GazelkaApiService)

## [2026-07-10] update | rating-board

- Создан новый topic: rating-board.md — доска рейтинга сотрудников
  - Лидеры и подиум: ТОЛЬКО швеи (`RATING_ROLE_IDS = [1]`), максимум 9 строк
  - Статистика: все 3 роли (швеи, закройщики, ОТК) по дням, период с 1-го числа
    месяца до ВЧЕРА
  - Медаль gold в статистике: только швея с дневным рекордом, закройщики/ОТК без
    медалей
  - Смена в статистике: из индивидуального расписания (`schedules`), не из
    `shift_schedule`
  - Фраза золотого медалиста: случайная из 100 вариантов (JS), меняется при
    смене лидера
  - Звук стикеров FBO/FBS: только при росте (`newValue > oldValue`)
  - Фикс пустого подиума: проверки `&& g1 && s1` в условиях свапов
  - Фронт очищен от демо-данных, заголовок статистики динамический (
    `now()->month`)
- Обновлён topic: shift-system.md — добавлено примечание об использовании
  `schedules` в статистике рейтинговой доски
- Затронутые файлы:
  - `app/Services/RatingBoard/RatingBoardDataService.php` — бизнес-логика (
    константы ролей, getStatistics переписан, buildLeaders лимит 9, новая логика
    смены)
  - `public/rating_board/js/app.js` — GOLD_PHRASES, applyPodiumDiff фикс,
    animateStickerValue звук при росте
  - `resources/views/rating_board/index.blade.php` — очищен от статичных данных,
    динамический месяц
  - `tests/Feature/RatingBoardDataServiceTest.php` — 11 новых тестов
    getStatistics

## [2026-07-12] add | chunks-auto-cleanup

- Добавлен ежедневный авто-очистка папки `chunks/` от брошенных chunked-загрузок
  видео — метод `MarketplaceSupplyService::deleteOldChunks(int $days = 1)`
  перебирает `Storage::directories('chunks')` (дефолтный диск `local` =
  `storage/app/private`) и удаляет папки старше порога через
  `Storage::deleteDirectory`. Логирует в канал `system`
- В `routes/console.php` добавлен `Schedule::call` → `deleteOldChunks(days: 1)`
  на
  `dailyAt('01:05')` — сразу после `deleteOldVideos` (01:00)
- **Бизнес-логика:** папка `chunks/{dzuuid}/N.part` — части видео для поставок
  маркетплейса (Dropzone.js → `chunkedUpload()`). Ранее папка удалялась ТОЛЬКО
  при успешной сборке видео; брошенные/оборванные загрузки копились навсегда.
  Теперь чистятся автоматом старше 1 суток. НЕ путать с `deleteOldVideos()` —
  тот
  чистит ГОТОВЫЕ видео старше 60 дней на `public` диске (
  `storage/app/public/videos`)
- Обновлены topics: marketplace-integration.md
- Обновлён map: services.md (добавлен метод deleteOldChunks)

## [2026-07-12] update | notifications-async

- Рефакторинг отправки TG/MAX уведомлений: синхронные точки переведены в
  асинхронные через Job'ы
-
`NotificationService::notifyAdmin(string $text, bool $queued=true, ?int $delaySeconds=null)` —
новый метод для admin-уведомлений (TG + MAX), заменяет 14 точек прямого вызова
`TgService::sendMessage(config('telegram.admin_id'), $text)`
- `NotificationService::notify($user, $text, queued, delaySeconds)` — все 4
  dispatch-точки теперь используют `->afterCommit()` (защита от отправки при
  rollback транзакции)
- Массовые рассылки (9 точек foreach) переведены на
  `notify($user, $text, queued: true, delaySeconds: $index+1)` — stagger 1с
  против 429
- Рассылка смены в `UserService::sendMessageForWorkingTodayEmployees()` — с
  прямых `TgService/MaxService::sendMessage` на
  `NotificationService::notify($schedule->user, $text, queued: true, delaySeconds: $i+1)`
- Синхронные точки (НЕ трогали): webhooks (TelegramController, MaxController),
  привязка аккаунтов (UsersController), dev-метод (SettingController::test)
- Circuit Breaker (429→30мин, 403→6ч) остался внутри TgService/MaxService без
  изменений
- Создан topic: notifications.md — архитектура TG/MAX рассылки (sync vs async,
  CB, afterCommit, NotificationService)
- Обновлён map: services.md (добавлен метод notifyAdmin)
- Обновлён topic: max-integration.md (связь с notifications.md)

## [2026-07-14] update | rating-board

- Добавлено поле `shift_done: bool` в таблицу лидеров — визуальная метка «смена
  закрыта» (иконка-замок + затемнение карточки)
- Источник данных: таблица `schedules` (shift_opened_time != '00:00:00' AND
  shift_closed_time != '00:00:00' для сегодняшней даты)
- НЕ влияет на попадание в таблицу лидеров (попадание по факту completed_at за
  сегодня)
- Фронт: `syncShiftDone(leaders)` вызывается в `applyPollDiff` и `renderInitial`
  на каждом poll, CSS класс `.leaders__item--done { opacity: .55 }`
- Тесты: +3 кейса на shift_done (закрыл/открыл но не закрыл/не открывал), всего
  35 passed
- Затронутые файлы: RatingBoardDataService.php (applyShiftDone),
  public/rating_board/js/app.js (syncShiftDone, LOCK_SVG),
  public/rating_board/css/style.css
- Обновлён topic: rating-board.md

## [2026-07-14] update | rating-board

- Добавлено скрытие таблицы лидеров и подиума когда ВСЕ лидеры закрыли смену (
  frontend-only)
- Новая функция `toggleAllShiftDone(leaders)`: проверяет
  `list.length > 0 && list.every(l => l.shift_done)` → добавляет класс
  `.all-shift-done` на `body`
- CSS: `.all-shift-done .leaders, .all-shift-done .top { display: none }`
  скрывает блоки таблицы лидеров и подиума. Блоки стикеров/статистики/winner
  остаются видимыми
- Вызывается на каждом poll в `renderInitial` и `applyPollDiff` (после
  `syncShiftDone`)
- ПУСТОЙ список лидеров (никто не выполнил заказы сегодня) НЕ триггерит
  скрытие (условие `length > 0`)
- Затронутые файлы: public/rating_board/js/app.js (toggleAllShiftDone),
  public/rating_board/css/style.css (all-shift-done)
- Обновлён topic: rating-board.md
