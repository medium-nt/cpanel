# Marketplace Integration — Интеграция Ozon и Wildberries

> Last reviewed: 2026-06-11

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
- `app/Services/MarketplaceItemService.php` — управление товарами
- `app/Services/AutoOrderService.php` — автоматическое пополнение
- `app/Services/UserService.php` — getListManagersWithTg() — получение tg_id
  менеджеров
- `app/Http/Controllers/MarketplaceSupplyController.php` — контроллер поставок
  (включая валидацию дат Газельки в `updateFbo()`)
- `app/Http/Controllers/SupplyBoxController.php` — сборка поставок +
  TG-уведомления
- `app/Models/MarketplaceOrder.php` — заказы
- `app/Models/MarketplaceOrderItem.php` — позиции заказов
- `app/Models/MarketplaceItem.php` — товары
- `app/Models/MarketplaceSupply.php` — поставки
- `app/Models/MarketplaceWarehouse.php` — склады маркетплейсов
- `routes/console.php` — расписание задач

## Бизнес-правила

- Два маркетплейса: Ozon и Wildberries — единый интерфейс
- FBS: продавец собирает и отправляет, FBO: маркетплейс хранит и отправляет
- Синхронизация заказов каждые 10 мин — критичный бизнес-процесс
- Видео упаковки хранится 60 дней для разрешения споров
- При отмене заказа — статус обновляется, материалы возвращаются
- Автообновление статусов поставок (`updateStatusSupply`, 02:00) применяется
  только к FBS (`type = 'FBS'`); FBO-поставки намеренно исключены
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

## Связанные topics

- [order-lifecycle.md](order-lifecycle.md) — статусная машина заказов
- [material-flow.md](material-flow.md) — материалы для производства
- [warehouse-operations.md](warehouse-operations.md) — складские операции
