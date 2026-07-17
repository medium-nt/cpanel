# Marketplace Integration — Интеграция Ozon и Wildberries

> Last reviewed: 2026-07-17

## Обзор

Система интегрируется с двумя маркетплейсами — Ozon и Wildberries — через их
API. Заказы автоматически импортируются каждые 10 минут. Поддерживаются модели
FBS (продавец отправляет) и FBO (маркетплейс хранит). Поставки создаются и
отправляются через API с отслеживанием статусов.

## Как это работает

### Синхронизация заказов (каждые 10 минут)

**Архитектура API-слоя:** после рефакторинга (12.07.2026) God-объект
MarketplaceApiService разделён на 4 файла:

1. **`app/Services/Marketplace/MakesApiRequests.php`** (trait, 64 строки) —
   переиспользуемая HTTP-обвязка: `ozonRequest()`, `wbRequest()`,
   `getOzonApiKey/SellerId()`, `getWbApiKey()` (protected static). Подключается
   во всех 3 сервисах.

2. **`app/Services/Marketplace/OzonApiService.php`** (~1870 строк, static) — 39
   public Ozon+Returns методов + 9 private helpers. Имена без суффикса Ozon
   (`collectOrder`, `getAllNewOrders`, `getBarcodeBySku`, `supply`,
   `getDraftInfo`,
   `createCargo`, `getReturnsList` и т.д.).

3. **`app/Services/Marketplace/WbApiService.php`** (~790 строк, static) — 14
   public WB методов + 6 private helpers. Имена без суффикса Wb (`collectOrder`,
   `getAllNewOrders`, `supply`, `getFboSupplies` и т.д.).

4. **`app/Services/MarketplaceApiService.php`** (~970 строк) — теперь ФАСАД-
   ДЕЛЕГАТОР + координатор:
    - **Делегаты 1:1** для обратной совместимости (все 53 старых имени методов
      сохранены, вызывают `OzonApiService::...` / `WbApiService::...`)
    - **Оркестрация:** `uploadingNewProducts()`, `uploadingCancelledProducts()`
      (cron каждые 10 мин) + их private helpers (getCancelledProductsOZON/WB,
      delete*, changeToFBO*, splittingOrdersWithMoreThanOneQuantity,
      checkCancelledProductsOzon)
    - **Роутеры по marketplace_id:** `getStatusOrder()` → OzonApiService/
      WbApiService::getStatusOrder(), `getReturnReason()` → OzonApiService/
      WbApiService::getReturnReason()
    - **Утилиты:** `splittingOrder()`, `getNotFoundSkus()`,
      `hasOrderInSystem()`,
      `hasSkuInSystem()`

**Обратная совместимость:** все 21 потребитель (контроллеры, сервисы,_jobs)
продолжают вызывать старые методы через фасад-делегатор — код потребителей не
трогался.

**Тесты:** 129 characterization-тестов в `MarketplaceApiServiceTest.php` (118
оригинальных + 11 BarcodeGenerationTest) — 100% coverage 59 public методов.
Зелёные после рефакторинга.

1. **Новые заказы:** `MarketplaceApiService::uploadingNewProducts()` —
   оркестратор вызывает `OzonApiService::getAllNewOrders()` /
   `WbApiService::getAllNewOrders()` и создаёт `MarketplaceOrder` +
   `MarketplaceOrderItem` в БД
2. **Отмены:** `MarketplaceApiService::uploadingCancelledProducts()` —
   оркестратор обрабатывает отменённые заказы через private helpers
   `getCancelledProductsOZON/WB()` и обновляет статусы

**B2B-признак заказов (is_b2b):**

- Поле `is_b2b` в `marketplace_orders` (boolean, default `false`) различает
  заказы от юридических (B2B) и физических лиц
- **Определяется ТОЛЬКО при автоимпорте** через API (каждые 10 мин):
    - **Ozon:** в `OzonApiService::getAllNewOrders()` — признак берётся из
      поля `legal_info` ответа `/v3/posting/fbs/unfulfilled/list`
      (проверяет `inn` или `company_name`)
    - **Wildberries:** в `WbApiService::getAllNewOrders()` — из поля
      `options.isB2B` ответа `/api/v3/orders/new`
- **Бизнес-правило:** заказы из других источников (Excel-импорт, ручное
  добавление, разбор FBO-поставок) остаются `is_b2b=false` — это НЕ баг,
  просто нет данных о типе заказчика в этих источниках
- FBO-заказа не проходят через `getAllNewOrders` (только FBS), поэтому
  B2B-флаг для них не определяется

### Ozon API (OzonApiService)

**Заказы:**

- `OzonApiService::getAllNewOrders()` — получение новых FBS-заказов
- `OzonApiService::collectOrder()` — подтверждение сборки заказа
- `MarketplaceApiService::getStatusOrder()` — роутер по marketplace_id,
  делегирует в `OzonApiService::getStatusOrder()`

**Поставки:**

- `OzonApiService::supply()` — создание и управление поставками
- `OzonApiService::createDraftDirect()` — FBO direct supply drafts
- `OzonApiService::createDraftCrossdock()` — FBO cross-dock drafts

**Идентификация маркетплейса:**

- `marketplace_id` в БД: 1 = OZON, 2 = Wildberries
- `Marketplace::NAME` содержит пути к иконкам (не текстовые имена)
- Для текстовых имён в уведомлениях используется `match` по `marketplace_id`

**Склады:**

- `OzonApiService::syncWarehouses()` — синхронизация складов Ozon (еженедельно в
  03:00)
- **Структура кластеров:** OZON (marketplace_id=1) — поле `cluster` =
  город-группировка
  (Казань, Краснодар), много складов мапятся на 1 кластер. WB (
  marketplace_id=2) —
  поле `cluster` пустое, кластером служит `name` (склад/город). Соответственно,
  "кластерное значение" = OZON→cluster, WB→name

**Штрихкоды и этикетки:**

- `OzonApiService::getBarcode()` — генерация штрихкодов Ozon
- `OzonApiService::getBarcodeFBO()` — этикетки FBO
- `OzonApiService::getPostingNumberByBarcode()` — поиск заказа по штрихкоду
- `OzonApiService::createCargo()` — создание грузоместа для короба поставки
- `OzonApiService::getCargoCreateInfo()` — получение результата создания
  грузоместа
- `OzonApiService::createCargoLabel()` — создание этикетки для грузоместа

**Возвраты:**

- `OzonApiService::getReturnsList()` — список возвратов
- `MarketplaceApiService::getReturnReason()` — роутер по marketplace_id,
  делегирует в `OzonApiService::getReturnReason()` /
  `WbApiService::getReturnReason()`

### Wildberries API (WbApiService)

**Заказы:**

- `WbApiService::getAllNewOrders()` — получение новых заказов
- `WbApiService::collectOrder()` — подтверждение сборки

**Поставки:**

- `WbApiService::supply()` — создание и управление поставками
- `WbApiService::getFboSupplies()` — получение FBO-поставок
- `WbApiService::getFboSupplyDetail()` — детали поставки
- `WbApiService::getFboSupplyGoods()` — товары в поставке
- **Лимит 100 заказов на FBS-поставку:** WB API ограничивает эндпоинт
  `/api/marketplace/v3/supplies/{supplyId}/orders` до 100 order_id за один
  PATCH.
  Реализовано через константу `WbApiService::MAX_ORDERS_PER_SUPPLY = 100`
  ✨ **НОВОЕ**
- **Механизм отправки заказов в FBS-поставку:** метод `addOrdersToSupply()`
  отправляет все order_id одним PATCH-запросом `{"orders": [...]}` вместо N
  последовательных запросов. Раньше пробивало rate-limit WB (300
  запросов/200мс).
  ✨ **НОВОЕ**
- **Safety-net при превышении лимита:** если `count(order_id) > 100` — запрос к
  WB
  API НЕ делается, все order_id возвращаются как «не добавленные» → `supply()`
  возвращает false с понятной причиной. ✨ **НОВОЕ**
- **Только для FBS:** FBO-поставки WB привязываются через
  `MarketplaceSupplyController::linkWbFbo` (поставка создаётся на стороне WB,
  локально лишь привязывается по ID) и через `addOrdersToSupply` НЕ проходят.
  ✨ **НОВОЕ**

**Склады:**

- `WbApiService::syncWarehouses()` — синхронизация складов WB (еженедельно в 03:
  00)

**Штрихкоды:**

- `WbApiService::getBarcode()` — генерация штрихкодов WB
- `WbApiService::getBarcodeFBO()` — этикетки FBO

### Управление товарами (MarketplaceItemService)

- Фильтрация товаров по названию, ширине, высоте
- Управление SKU для Ozon и WB
- Отслеживание потребления материалов (MaterialConsumption)
- **Динамические фильтры для UI:** методы `getAllTitleMaterials()`,
  `getAllWidthMaterials()`, `getAllHeightMaterials()` возвращают уникальные
  значения из `marketplace_items` для populate-фильтров на странице стикеровки
  и в других местах интерфейса

### Управление поставками (MarketplaceSupplyService)

- Видео упаковки: загрузка чанками (Dropzone.js → `chunkedUpload()`), сборка на
  сервере
- Удаление старых видео (>60 дней) ежедневно в 01:00 — метод `deleteOldVideos()`
  чистит ГОТОВЫЕ видео на `public` диске (`storage/app/public/videos`)
- Удаление брошенных chunked-загрузок (>1 дня) ежедневно в 01:05 — метод
  `deleteOldChunks(days: 1)` чистит папку `chunks/` на `local` диске (
  `storage/app/private`) от частей видео, которые не были собраны (оборванные
  загрузки, ошибки сети)
- Обновление статусов поставок через API ежедневно в 02:00 (**только FBS**)
- **Откат отгрузки FBO-поставки:** метод
  `MarketplaceSupplyController::unmarkShipped()`
  позволяет администратору вернуть поставку из статуса 3 (Закрытая/Отгружена)
  обратно
  в статус 4 (Отгрузка), чтобы снова стало доступно редактирование (стикер,
  накладная,
  состав). Меняет ТОЛЬКО `marketplace_supplies.status`: 3 → 4. `completed_at` НЕ
  трогается (сохраняется значение). НЕ затрагивает заказы, короба, остатки,
  историю.
  Доступ: только админ (policy `unmarkShipped` → isAdmin()). Guard: если
  `status !== 3`
  → redirect back с error «Поставка не отгружена.» ✨ **НОВОЕ**
- Автообновление применяется только к поставкам с `type = 'FBS'`: метод
  `updateStatusSupply()` опрашивает статусы заказов через FBS-эндпоинты
  (Ozon `/posting/fbs/get`, WB `/orders/status`) и закрывает поставку
  (`status` 4 → 3, `completed_at`), когда у всех заказов новые статусы.
  FBO-поставки исключены — у них другая API-семантика статусов.
- Синхронизация статусов при завершении поставки

### Cron-расписание

| Время                | Задача                                       |
|----------------------|----------------------------------------------|
| Каждые 10 мин        | Синхронизация новых и отменённых заказов     |
| Каждые 30 мин (7-20) | Автоматический заказ материалов              |
| 01:00                | Удаление старых видео упаковки (>60 дней)    |
| 01:05                | Удаление брошенных chunked-загрузок (>1 дня) |
| 02:00                | Обновление статусов поставок (только FBS)    |
| 03:00 (пн)           | Синхронизация складов Ozon/WB                |

## Ключевые файлы

**API-слой (маркетплейсы):**

- `app/Services/MarketplaceApiService.php` — фасад-делегатор + координатор (~970
  строк).
  53 метода-делегата для обратной совместимости + оркестрация
  (`uploadingNewProducts`, `uploadingCancelledProducts`) + роутеры по
  marketplace_id (`getStatusOrder`, `getReturnReason`) + утилиты
- `app/Services/Marketplace/OzonApiService.php` — 39 методов Ozon API (~1870
  строк, static). Использует trait MakesApiRequests
- `app/Services/Marketplace/WbApiService.php` — 14 методов WB API (~790 строк,
  static). Использует trait MakesApiRequests
- `app/Services/Marketplace/MakesApiRequests.php` — trait с переиспользуемой
  HTTP-обвязкой (64 строки): `ozonRequest()`, `wbRequest()`, getters для
  API-ключей
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
  менеджеров (для TG-уведомлений; для MAX используется аналогичный подход с
  max_id)
- `app/Http/Controllers/MarketplaceSupplyController.php` — контроллер поставок
  (включая валидацию дат Газельки в `updateFbo()`, методы `markShipped()` и
  `unmarkShipped()` — отмена отгрузки FBO-поставки). ✨ **НОВОЕ**
  **Багфикс:** метод `markShipped()` НЕ имеет guard на текущий статус (проверяет
  только status===3 от повторной отгрузки). Прямой вызов роута mark_shipped для
  поставки в status 0/13 технически возможен — известная дыра, сознательно пока
  не
  закрыта (фикс был только UI). ✨ **НОВОЕ**
- `app/Http/Controllers/SupplyBoxController.php` — сборка поставок +
  TG+MAX-уведомления (параллельно) + генерация стикеров для коробов (OZON через
  API,
  WB через PDF, с возможностью перегенерации)
- `app/Models/MarketplaceOrder.php` — заказы с полем `is_b2b` (boolean,
  признак B2B-заказа от юрлица, заполняется при автоимпорте из API)
- `app/Models/MarketplaceOrderItem.php` — позиции заказов
- `app/Models/MarketplaceItem.php` — товары
- `app/Models/MarketplaceSupply.php` — поставки
- `app/Livewire/BoxOrderScanner.php` — сканер ШК для добавления заказов в короб
  FBO-поставки с сортировкой по boxed_at и группировкой по товару ✨ **НОВОЕ**
- `app/Models/MarketplaceWarehouse.php` — справочник складов маркетплейсов с
  методами
  `clustersByMarketplace()` — возвращает кластерные значения для OZON (по полю
  cluster)
  и WB (по полю name), и `clusterOptions()` — опции для select-настройки
  приоритета
- `app/Policies/MarketplaceSupplyPolicy.php` — политика `deleteOrders()`,
  `detachOrders()` для контроля доступа к удалению и отвязке заказов, а также
  `unmarkShipped()` — проверка isAdmin() для отмены отгрузки ✨ **НОВОЕ**
- `routes/console.php` — расписание задач
- `routes/marketplace_supplies.php` — роут `marketplace_supplies.unmark_shipped`
  (GET /{marketplace_supply}/unmark-shipped, ->can('unmarkShipped')) ✨ **НОВОЕ**

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

**Отмена отгрузки FBO-поставки (откат):** ✨ **НОВОЕ**

- **Доступно только:** администраторам (policy `unmarkShipped()` → `isAdmin()`)
- **Условие:** поставка в статусе 3 (Закрытая/Отгружена)
- **Действие:** переводит поставку из статуса 3 → 4 (Отгрузка)
- **Бизнес-смысл:** вернуть поставку в редактируемый状态 (стикер, накладная,
  состав)
  после ошибочной отгрузки
- **Ограничения:**
    - Меняет ТОЛЬКО `marketplace_supplies.status`: 3 → 4
    - `completed_at` НЕ трогается (сохраняется значение из `markShipped`)
    - НЕ затрагивает заказы (`marketplace_orders` уже в status=3), короба,
      остатки, историю
- **Guard:** если `status !== 3` → redirect back с error «Поставка не
  отгружена.»
- **UI:** кнопка «Отменить отгрузку» на странице поставки при
  `status===3 && admin`
- **Лог:** `Log::channel('marketplace_supplies')->notice(...)` — «отменил
  отгрузку поставки #ID»
- **Зеркальность:** метод `unmarkShipped()` зеркален к `markShipped()`, но не
  трогает `completed_at`

**Excel-импорт заказов:**

- `ExcelOrderImport::mount()` — select складов теперь строится через
  `MarketplaceWarehouse::clustersByMarketplace()`.
  Для OZON в select показываются города-кластеры (cluster), для WB — склады (
  name)
- При импорте заказов поле `marketplace_orders.cluster` заполняется корректным
  кластерным
  значением (OZON=город, WB=склад), что обеспечивает работу кластерной
  приоритизации
  (`orders_cluster_priority`)
- **Цеховая настройка `orders_cluster_priority`** (формат
  `<marketplace_id>|<cluster>`,
  напр. `1|Казань`, `2|Коледино`) — приоритизация выдачи новых заказов швеям по
  FBO-кластеру. Применяется ПЕРВЫМ в цепочке сортировки (CASE WHEN), главнее
  `orders_priority`. Пусто = выключено. **Авто-сброс:** при переводе последнего
  заказа приоритетного кластера в стикеровку (status=5) настройка автоматически
  обнуляется. Учитывает заказы в очереди (статусы 0,4,7,8) в этом цехе ИЛИ новые
  нераспределённые (`workshop_id IS NULL`). Сбрасывает только цеховые настройки
  (`workshop_id` не NULL), глобальную не трогает. Логирует в канал `system`.
- **Валидация даты Газельки:** при редактировании FBO-поставки поля
  `gazelka_shipment_date` (дата отгрузки в Газельку) и `supply_date` (дата
  поставки в маркетплейс) **оба редактируются вручную** пользователем в форме
  `edit-fbo.blade.php`. `gazelka_shipment_date` должна быть **строго раньше**
  `supply_date` минимум на 1 день. Совпадение дат запрещено.
  Газелька — транспорт, который доставляет товар до маркетплейса, поэтому
  отгрузка должна произойти за день до приёмки. Проверка: бекенд
  (`MarketplaceSupplyController::updateFbo()`) — проверка идёт по НОВЫМ
  значениям
  обоих полей (или старым, если не переданы); фронтенд — `min` атрибут на input
  `supply_date` = `gazelka_shipment_date + 1 день` ✨ **ОБНОВЛЕНО**
- **Кнопка «Поставка отгружена» (markShipped) в FBO-поставках:** ✨ **НОВОЕ**
    - **Бизнес-правило:** кнопка видна ТОЛЬКО при статусе 4 («Отгрузка»).
      Жизненный цикл FBO: `0 (Открытая) → 13 (Сформирована/На сборке) → 4
      (Отгрузка) → 3 (Закрытая)`
    - **OZON FBO:** явное условие `status == 4` в
      `resources/views/marketplace_supply/show-ozon-fbo.blade.php:237`.
      Раньше было `status !== 3` (баг — кнопка была видна при 0, 4, 13)
    - **WB FBO:** косвенная завязка через стикер — `$supply->sticker &&
      status !== 3` в `show-wb-fbo.blade.php:117`. Работает корректно, потому
      что
      стикер можно загрузить ТОЛЬКО при status=4 (`show-wb-fbo.blade.php:151`)
    - **Эталон:** условие `status == 4` уже использовалось в
      `resources/views/marketplace_supply/show.blade.php:118`
    - **Технический нюанс:** контроллер `markShipped` НЕ имеет guard на текущий
      статус (проверяет только status===3 от повторной отгрузки). Прямой вызов
      роута mark_shipped для поставки в status 0/13 технически возможен —
      известная
      дыра, сознательно пока не закрыта (фикс был только UI)
- **TG+MAX-уведомления при сборке поставки:** при успешной сборке (статус → 4)
  отправляются уведомления админу и всем активным менеджерам с привязанным
  Telegram или MAX (параллельно). Используются `SendTelegramMessageJob` и
  `SendMaxMessageJob` с задержкой для rate limits. Имя маркетплейса определяется
  через `match` по `marketplace_id` (1=OZON, 2=Wildberries), т.к.
  `Marketplace::NAME`
  содержит пути к иконкам.

**Cooldown уведомлений "нет материала":**

- `MarketplaceOrderItemService::notifyNoMaterials()` — отправка уведомления
  админу
  о нехватке материала по конкретному товару при нажатии «Получить новый заказ»
- **Cooldown:** отправка блокируется на 30 мин через Cache-флаг
  `no_material:item:{itemId}` → защита от спама (раньше каждый клик плодил
  копии)
- **Бизнес-смысл:** сотрудник видит уведомление «нет материала» и нажимает F5
  для
  повторного получения заказа → без cooldown было бы N копий одного уведомления
- **Логика:** проверка флага в начале метода → при наличии — тихий return без
  отправки; после отправки — флаг ставится на 30 мин
- **Лог:** канал `items` (info) — «уведомлён админ о нехватке материала для
  товара
  #{itemId}»
- **Перегенерация стикеров OZON:** для коробов OZON-поставок добавлена
  возможность
  перегенерации стикера через query-параметр `?regenerate=1`. Это обнуляет
  `sticker_url` в БД и повторно вызывает цепочку API-запросов OZON
  (createCargoLabelOzon). Грузоместо не пересоздаётся — используется
  кешированный
  `cargo_id`. В UI добавлена кнопка-иконка (🔄) рядом с «Распечатать стикер»,
  видна только для OZON коробов (marketplace_id === 1).

### Сборка коробов FBO-поставки (BoxOrderScanner)

**Страница сканирования:**
`/megatulle/marketplace_supplies/{supply}/boxes/{box}` — Livewire-компонент
`BoxOrderScanner`.

**Бизнес-смысл:** Кладовщик сканирует ШК товаров, система автоматически находит
заказ в поставке и добавляет его в короб. Порядок заказов в таблице отражает
хронологию сканирования с группировкой по товару.

**Ключевое поле:**

- `marketplace_orders.boxed_at` (timestamp, nullable) — время добавления заказа
  в короб. Заполняется при сканировании, сбрасывается при отвязке от короба.
  Сохраняется персистентно (перезагрузка страницы не меняет порядок).

**Логика сортировки (render):**

1. Последний отсканированный заказ всегда в первой строке (
   `sortByDesc('boxed_at')`)
2. Все заказы с тем же товаром (`marketplace_item_id`) подтягиваются следом за
   ним (`groupBy(marketplace_item_id)`)
3. Группы сортируются по максимальному `boxed_at` в группе (
   `sortByDesc(max('boxed_at'))`)
4. Порядок персистентен — после перезагрузки страницы сохраняется

**Обоснование:** Легко увидеть, какие товары только что добавлены (вверху), и
сразу найти все экземпляры того же товара (сгруппированы рядом). Удобно для
контроля правильности сборки.

**Методы:**

- `handleScan()` — поиск заказа по ШК, привязка к коробу (`box_id`,
  `boxed_at = now()`)
- `removeOrder()` — отвязка от короба (`box_id = null`, `boxed_at = null`)
- `render()` — сортировка с группировкой по товару

**Файлы:**

- `app/Livewire/BoxOrderScanner.php` — Livewire-компонент
- `resources/views/livewire/box-order-scanner.blade.php` — view
- `app/Models/MarketplaceOrder.php` — поле `boxed_at`, cast `datetime`, fillable
-
`database/migrations/2026_06_26_061618_add_boxed_at_to_marketplace_orders_table.php` —
миграция с backfill

## Связанные topics

- [order-lifecycle.md](order-lifecycle.md) — статусная машина заказов и правила
  удаления
- [material-flow.md](material-flow.md) — материалы для производства
- [warehouse-operations.md](warehouse-operations.md) — складские операции
