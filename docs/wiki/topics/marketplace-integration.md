# Marketplace Integration — Интеграция Ozon и Wildberries

> Last reviewed: 2026-06-24

## Обзор

Система интегрируется с двумя маркетплейсами — Ozon и Wildberries — через их
API. Заказы автоматически импортируются каждые 10 минут. Поддерживаются модели
FBS (продавец отправляет) и FBO (маркетплейс хранит). Поставки создаются и
отправляются через API с отслеживанием статусов.

## Как это работает

### Синхронизация заказов (каждые 10 минут)

**MarketplaceApiService** — ключевой сервис с 59 методами:

1. **Новые заказы:** `uploadingNewProducts()` — забирает новые заказы из Ozon/WB
   и создаёт `MarketplaceOrder` + `MarketplaceOrderItem` в БД
2. **Отмены:** `uploadingCancelledProducts()` — обрабатывает отменённые заказы,
   обновляет статусы

### Ozon API

**Заказы:**

- `getAllNewOrdersOzon()` — получение новых FBS-заказов
- `collectOrderOzon()` — подтверждение сборки заказа
- `getStatusOrder()` — проверка текущего статуса

**Поставки:**

- `ozonSupply()` — создание и управление поставками
- `createDraftDirectOzon()` — FBO direct supply drafts
- `createDraftCrossdockOzon()` — FBO cross-dock drafts

**Идентификация маркетплейса:**

- `marketplace_id` в БД: 1 = OZON, 2 = Wildberries
- `Marketplace::NAME` содержит пути к иконкам (не текстовые имена)
- Для текстовых имён в уведомлениях используется `match` по `marketplace_id`

**Склады:**

- `syncWarehousesOzon()` — синхронизация складов Ozon (еженедельно в 03:00)

**Штрихкоды и этикетки:**

- `getBarcodeOzon()` — генерация штрихкодов Ozon
- `getBarcodeOzonFBO()` — этикетки FBO
- `getOzonPostingNumberByBarcode()` — поиск заказа по штрихкоду
- `createCargoOzon()` — создание грузоместа для короба поставки
- `getCargoCreateInfoOzon()` — получение результата создания грузоместа
- `createCargoLabelOzon()` — создание этикетки для грузоместа

**Возвраты:**

- `getReturnsList()` — список возвратов
- `getReturnReason()` — причины возвратов

### Wildberries API

**Заказы:**

- `getAllNewOrdersWb()` — получение новых заказов
- `collectOrderWb()` — подтверждение сборки

**Поставки:**

- `wbSupply()` — создание и управление поставками
- `getFboSuppliesWb()` — получение FBO-поставок
- `getFboSupplyDetailWb()` — детали поставки
- `getFboSupplyGoodsWb()` — товары в поставке

**Склады:**

- `syncWarehousesWb()` — синхронизация складов WB (еженедельно в 03:00)

**Штрихкоды:**

- `getBarcodeWb()` — генерация штрихкодов WB
- `getBarcodeWBFBO()` — этикетки FBO

### Управление товарами (MarketplaceItemService)

- Фильтрация товаров по названию, ширине, высоте
- Управление SKU для Ozon и WB
- Отслеживание потребления материалов (MaterialConsumption)
- **Динамические фильтры для UI:** методы `getAllTitleMaterials()`,
  `getAllWidthMaterials()`, `getAllHeightMaterials()` возвращают уникальные
  значения из `marketplace_items` для populate-фильтров на странице стикеровки
  и в других местах интерфейса

### Управление поставками (MarketplaceSupplyService)

- Видео упаковки: загрузка чанками, сборка на сервере
- Удаление старых видео (>60 дней) ежедневно в 01:00
- Обновление статусов поставок через API ежедневно в 02:00 (**только FBS**)
- Автообновление применяется только к поставкам с `type = 'FBS'`: метод
  `updateStatusSupply()` опрашивает статусы заказов через FBS-эндпоинты
  (Ozon `/posting/fbs/get`, WB `/orders/status`) и закрывает поставку
  (`status` 4 → 3, `completed_at`), когда у всех заказов новые статусы.
  FBO-поставки исключены — у них другая API-семантика статусов.
- Синхронизация статусов при завершении поставки

### Cron-расписание

| Время                | Задача                                    |
|----------------------|-------------------------------------------|
| Каждые 10 мин        | Синхронизация новых и отменённых заказов  |
| Каждые 30 мин (7-20) | Автоматический заказ материалов           |
| 01:00                | Удаление старых видео упаковки            |
| 02:00                | Обновление статусов поставок (только FBS) |
| 03:00 (пн)           | Синхронизация складов Ozon/WB             |

## Ключевые файлы

- `app/Services/MarketplaceApiService.php` — 59 методов API-интеграции
- `app/Services/MarketplaceSupplyService.php` — управление поставками
- `app/Services/MarketplaceOrderService.php` — сервис удаления и отвязки заказов
  в
  FBO-поставках (методы `delete()`, `deleteNewOrdersBySupply()` и
  `detachNotReadyOrdersBySupply()` и `detachOnSupplyOrdersBySupply()`) ✨ **НОВОЕ
  **
- `app/Http\Controllers/MarketplaceOrderController.php` — контроллер с методами
  `destroyNewBySupply()` (массовое удаление) и `detachNotReadyBySupply()` (
  массовая отвязка не готовых заказов) и
  `detachOnSupplyBySupply()` (массовая отвязка заказов на поставку) ✨ **НОВОЕ**
  массовая
  отвязка заказов)
- `app/Services/MarketplaceItemService.php` — управление товарами +
  динамические фильтры (getAllTitleMaterials, getAllWidthMaterials,
  getAllHeightMaterials)
- `app/Services/AutoOrderService.php` — автоматическое пополнение
- `app/Services/UserService.php` — getListManagersWithTg() — получение tg_id
  менеджеров
- `app/Http/Controllers/MarketplaceSupplyController.php` — контроллер поставок
  (включая валидацию дат Газельки в `updateFbo()`)
- `app/Http/Controllers/SupplyBoxController.php` — сборка поставок +
  TG-уведомления + генерация стикеров для коробов (OZON через API,
  WB через PDF, с возможностью перегенерации)
- `app/Models/MarketplaceOrder.php` — заказы
- `app/Models/MarketplaceOrderItem.php` — позиции заказов
- `app/Models/MarketplaceItem.php` — товары
- `app/Models/MarketplaceSupply.php` — поставки
- `app/Models/MarketplaceWarehouse.php` — склады маркетплейсов
- `app/Policies/MarketplaceSupplyPolicy.php` — политика `deleteOrders()` и
  `detachOrders()` для контроля доступа к удалению и отвязке заказов
- `routes/console.php` — расписание задач

## Бизнес-правила

- Два маркетплейса: Ozon и Wildberries — единый интерфейс
- FBS: продавец собирает и отправляет, FBO: маркетплейс хранит и отправляет
- Синхронизация заказов каждые 10 мин — критичный бизнес-процесс
- Видео упаковки хранится 60 дней для разрешения споров
- При отмене заказа — статус обновляется, материалы возвращаются
- Автообновление статусов поставок (`updateStatusSupply`, 02:00) применяется
  только к FBS (`type = 'FBS'`); FBO-поставки намеренно исключены
- **Удаление заказов в FBO-поставках:** администраторы могут удалять заказы
  только в статусе "новый" (0) из FBO-поставок, когда поставка в статусе 0 (
  формируется)
- Каскадное удаление через FK удаляет связанные позиции заказов и историю

**Отвязка заказов в FBO-поставках:** администраторы могут массово отвязывать
заказы от поставки, когда она в статусе 13 ("На сборка")

- **Три варианта отвязки:** ✨ **НОВОЕ**
    1. **"Убрать не готовые"** (`status = 4`) — заказы без короба
    2. **"Убрать на поставку"** (`status = 6`) — заказы без короба
    3. **"Удалить все новые"** (`status = 0`) — полное удаление заказов

**"Убрать не готовые":**

- Критерий: заказы без короба (`box_id IS NULL`) и в работе (`status = 4`)
- Отвязка НЕ удаляет заказы — только устанавливает `supply_id = null`
- Заказы в коробах (`box_id != null`) всегда остаются в поставке

**"Убрать на поставку" (новое):** ✨ **НОВОЕ**

- Критерий: заказы без короба (`box_id IS NULL`) и статус "На поставку" (
  `status = 6`)
- Бизнес-смысл: перед сборкой убрать заказы, которые ожидают отгрузки

**"Удалить все новые":**

- Только для FBO-поставок в статусе 0 (формируются)
- Полное удаление из системы с каскадным удалением позиций и истории
- **Валидация даты Газельки:** при редактировании FBO-поставки дата отгрузки в
  Газельку (`gazelka_shipment_date`) должна быть **строго раньше** даты отгрузки
  в маркетплейс (`supply_date`) минимум на 1 день. Совпадение дат запрещено.
  Газелька — транспорт, который доставляет товар до маркетплейса, поэтому
  отгрузка должна произойти за день до приёмки. Проверка: бекенд
  (`MarketplaceSupplyController::updateFbo()`) + фронтенд (`max` атрибут на
  input даты = `supply_date - 1 день`)
- **TG-уведомления при сборке поставки:** при успешной сборке (статус → 4)
  отправляются уведомления админу и всем активным менеджерам с привязанным
  Telegram. Используется `SendTelegramMessageJob` с задержкой для rate limits.
  Имя маркетплейса определяется через `match` по `marketplace_id`
  (1=OZON, 2=Wildberries), т.к. `Marketplace::NAME` содержит пути к иконкам.
- **Перегенерация стикеров OZON:** для коробов OZON-поставок добавлена
  возможность
  перегенерации стикера через query-параметр `?regenerate=1`. Это обнуляет
  `sticker_url` в БД и повторно вызывает цепочку API-запросов OZON
  (createCargoLabelOzon). Грузоместо не пересоздаётся — используется
  кешированный
  `cargo_id`. В UI добавлена кнопка-иконка (🔄) рядом с «Распечатать стикер»,
  видна только для OZON коробов (marketplace_id === 1).

## Связанные topics

- [order-lifecycle.md](order-lifecycle.md) — статусная машина заказов и правила
  удаления
- [material-flow.md](material-flow.md) — материалы для производства
- [warehouse-operations.md](warehouse-operations.md) — складские операции
