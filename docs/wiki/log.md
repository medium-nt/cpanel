# Wiki Change Log

> Append-only лог изменений wiki. Greppable:
`grep "^## \[" docs/wiki/log.md | tail -10`

## [2026-06-10] generate | Initial wiki generation

- Created INDEX.md, 6 map files (models, services, controllers, routes,
  livewire, schedule)
- Created .registry.json
- Models: 35 | Services: 23 | Controllers: 39 | Livewire: 12 | Route files: 30 |
  Cron: 9
- Topics: not yet created (Phase B pending)

## [2026-06-11] update | marketplace-integration

- `MarketplaceSupplyService::updateStatusSupply()`: ежедневный опрос статусов
  поставок (02:00) теперь ограничен FBS-поставками (`->where('type', 'FBS')`).
  FBO-поставки исключены — эндпоинты опроса статусов FBS-специфичные
  (Ozon `/posting/fbs/get`, WB `/orders/status`).
- Обновлён topic `marketplace-integration.md` (раздел «Управление поставками»,
  cron-таблица, бизнес-правила).

## [2026-06-11] update | gazelka-date-validation

- Добавлена валидация даты отгрузки в Газельку при редактировании FBO-поставки:
  `gazelka_shipment_date` должна быть строго раньше `supply_date` минимум на 1
  день.
- Бекенд: `MarketplaceSupplyController::updateFbo()` — проверка + редирект с
  ошибкой.
- Фронтенд: `edit-fbo.blade.php` — `max` атрибут на input даты =
  `supply_date - 1 день`.
- Обновлён topic: `marketplace-integration.md` (бизнес-правила, ключевые файлы).

## [2026-06-11] update | telegram-notifications-supply-assembly

- `SupplyBoxController::markAssembled()`: при сборке поставки (статус → 4)
  отправляются TG-уведомления админу и менеджерам с привязанным Telegram.
  Используется `SendTelegramMessageJob` с задержкой для rate limits.
- `UserService::getListManagersWithTg()`: новый метод — возвращает коллекцию
  tg_id активных менеджеров (не удалённых, с `tg_id != null`).
- Имя маркетплейса определяется через `match` по `marketplace_id`
  (1=OZON, 2=Wildberries), т.к. `Marketplace::NAME` содержит пути к иконкам.
- Обновлены topics: `warehouse-operations.md` (сборка поставок),
  `marketplace-integration.md` (TG-уведомления, идентификация маркетплейса).

## [2026-06-11] update | kiosk-roll-shift-isolation

- Добавлена изоляция рулонов по сменам в киоске (`/kiosk/rolls`).
- `StickerPrintingController`: 4 эндпоинта теперь проверяют `shift_id` рулона:
  `rolls()`, `completeRoll()`, `getRollByCode()`, `saveDefects()`.
- Правила: швеи, закройщики, ОТК работают только с рулонами своей смены.
  Админ и кладовщик имеют полный доступ (без фильтра).
- При попытке работы с рулоном другой смены → ошибка "Этот рулон принадлежит
  другой смене".
- В blade-шаблон добавлена колонка "Смена" перед "Статус".
- Добавлены 16 тестов в `KioskRollShiftIsolationTest`.
- Обновлены topics: `shift-system.md` (изоляция рулонов), `material-flow.md`
  (жизненный цикл рулонов с привязкой к сменам).

## [2026-06-11] update | dynamic-filters-sticker-printing

- `StickerPrintingController::index()`: захардкоженные фильтры (материалы,
  ширины, высоты) заменены на динамические, подтягиваемые из БД через
  `MarketplaceItemService::getAllTitleMaterials()`,
  `getAllWidthMaterials()`, `getAllHeightMaterials()`.
- `resources/views/sticker_printing.blade.php`: 3 блока `<option>` заменены на
  `@foreach` циклы с динамическими данными.
- Паттерн уже использовался в 4 других контроллерах
  (WarehouseOfItemController, MarketplaceOrderItemController,
  MarketplaceItemController), теперь применён и в StickerPrintingController.
- При добавлении нового товара в `marketplace_items` его материал/ширина/высота
  автоматически появляются в фильтрах.
- Обновлены topics: `warehouse-operations.md` (стикеровка),
  `marketplace-integration.md`
  (MarketplaceItemService — динамические фильтры).

## [2026-06-11] update | ozon-sticker-regeneration

- Добавлена возможность перегенерации стикера OZON для короба поставки через
  query-параметр `?regenerate=1`. Раньше URL стикера кешировался в `sticker_url`
  и никогда не обновлялся — если URL протухал, пользователь застревал. Теперь
  параметр обнуляет `sticker_url` и запускает перегенерацию этикетки через OZON
  API (createCargoLabelOzon), без пересоздания грузоместа (используется
  кешированный `cargo_id`).
- Бекенд: `SupplyBoxController::printSticker()` — прокидывает флаг `regenerate`
  в `printOzonSticker()`, который при `true` обнуляет `sticker_url` перед
  генерацией.
- Фронтенд: `supply_box/show.blade.php` — добавлена кнопка-иконка (🔄)
  «Перегенерировать стикер» рядом с «Распечатать стикер», видна только для
  OZON коробов (`marketplace_id === 1`).
- Обновлён topic: `marketplace-integration.md` (API-методы createCargoOzon,
  getCargoCreateInfoOzon, createCargoLabelOzon; бизнес-правило перегенерации;
  ключевые файлы).
