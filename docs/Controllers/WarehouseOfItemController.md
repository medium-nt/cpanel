# WarehouseOfItemController

**Путь:** `app/Http/Controllers/WarehouseOfItemController.php`

**Описание:** Контроллер для управления товарами на складе, сборкой заказов и
печатью стикеров

**Зависимости:**

- `App\Http\Requests\SaveGroupWarehouseOfItemRequest` - Form Request для
  валидации
- `App\Models\MarketplaceItem` - модель товара маркетплейса
- `App\Models\MarketplaceOrder` - модель заказа маркетплейса
- `App\Models\MarketplaceOrderItem` - модель элемента заказа
- `App\Models\Shelf` - модель полки
- `App\Models\Sku` - модель SKU
- `App\Services\MarketplaceApiService` - API сервис маркетплейсов
- `App\Services\MarketplaceItemService` - сервис товаров
- `App\Services\MarketplaceOrderItemService` - сервис элементов заказов
- `App\Services\MarketplaceOrderService` - сервис заказов
- `App\Services\WarehouseOfItemService` - сервис склада
- `Barryvdh\DomPDF\Facade\Pdf` - генератор PDF

---

## Методы контроллера

### index(Request $request, WarehouseOfItemService $warehouseOfItemService)

- **Описание:** Отображение списка товаров на складе
- **Параметры:**
    - `$request` (Request) - HTTP запрос с фильтрами
    - `$warehouseOfItemService` (WarehouseOfItemService) - сервис склада
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `materials`, `widths`, `heights` - фильтры для товаров
    - `shelves` (Collection) - все полки
    - `totalItems` (int) - общее количество товаров
    - `items` (LengthAwarePaginator) - пагинированный список товаров
- **View:** `warehouse_of_item.index`

### newRefunds(Request $request, WarehouseOfItemService $warehouseOfItemService)

- **Описание:** Обработка возвратов товаров
- **Параметры:**
    - `$request` (Request) - HTTP запрос с баркодом
    - `$warehouseOfItemService` - сервис склада
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `marketplace_item` (MarketplaceItem|null) - найденный товар
    - `marketplace_items` (Collection) - доступные товары
    - `barcode` (string) - отсканированный баркод
    - `message` (string) - сообщение о результате
    - `shelves` (Collection) - полки для размещения
    - `returnReason` (string) - причина возврата
- **View:** `warehouse_of_item.new_refunds`

### getStorageBarcodeFile(Request $request, WarehouseOfItemService $service)

- **Описание:** Генерация PDF с штрихкодами хранения
- **Параметры:**
    - `$request` (Request) - запрос с ID товаров
    - `$service` - сервис склада
- **Возвращает:** PDF файл или 404 ошибку
- **Логика:**
    - Ищет товары по ID
    - Генерирует штрихкоды хранения
    - Создает PDF документ

### saveStorage(Request $request, MarketplaceOrderItem $marketplace_item, WarehouseOfItemService $service)

- **Описание:** Сохранение товара на склад
- **Параметры:**
    - `$request` - запрос с ID полки
    - `$marketplace_item` - элемент заказа
    - `$service` - сервис склада
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Проверки:**
    - Наличие штрихкода хранения
    - Выбор полки
- **Логика:** Сохраняет товар на указанную полку

### toPickList()

- **Описание:** Список товаров для подбора со склада
- **Возвращает:** `Illuminate\View\View`
- **Использует:** `MarketplaceOrderService`
- **Данные:**
    - `orders` - заказы для подбора
    - `ordersAssembled` - собранные заказы
- **View:** `warehouse_of_item.to_pick_list`

### toPickListPrint(MarketplaceOrderService $service)

- **Описание:** Печать списка для подбора
- **Параметры:** `$service` - сервис заказов
- **Возвращает:** PDF файл
- **Логика:**
    - Получает заказы для подбора
    - Группирует товары
    - Генерирует PDF

### toPick(MarketplaceOrder $order, Request $request)

- **Описание:** Страница сборки заказа
- **Параметры:**
    - `$order` - заказ
    - `$request` - запрос с баркодом
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `itemName` - наименование товара
    - `order` - заказ
    - `item` - найденный товар по баркоду
    - `itemsCount` - общее количество
    - `shelfStats` - статистика по полкам

### labeling(Request $request, MarketplaceOrder $marketplaceOrder, MarketplaceOrderItem $marketplaceOrderItem)

- **Описание:** Передача товара на стикеровку
- **Параметры:**
    - `$request` - HTTP запрос
    - `$marketplaceOrder` - заказ маркетплейса
    - `$marketplaceOrderItem` - элемент заказа
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:**
    - Отправка запрос в API маркетплейса (Ozon/WB)
    - Логирование операции
    - Обмен товарами между заказами при необходимости
    - Изменение статусов заказа и товара

### done(MarketplaceOrder $marketplaceOrder)

- **Описание:** Завершение сборки заказа
- **Параметры:** `$marketplaceOrder` - заказ
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Проверки:** Наличие распечатанного стикера
- **Логика:**
    - Изменяет статус заказа на "на поставку"
    - Устанавливает время завершения
    - Изменяет статус товара

### toWork(MarketplaceOrder $marketplaceOrder)

- **Описание:** Возврат заказа в цех на пошив
- **Параметры:** `$marketplaceOrder` - заказ
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Проверки:** Статус заказа должен быть "в сборке"
- **Логика:**
    - Восстанавливает заказ из истории
    - Создает новый элемент заказа
    - Изменяет статус на "новый"

### addGroup()

- **Описание:** Форма добавления товаров партией
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - Материалы, размеры, высоты для выбора
    - Список полок
- **View:** `warehouse_of_item.add_group`

### saveGroup(SaveGroupWarehouseOfItemRequest $request, WarehouseOfItemService $service)

- **Описание:** Сохранение товаров партией
- **Параметры:**
    - `$request` - валидированные данные
    - `$service` - сервис склада
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:**
    - Поиск товара по параметрам
    - Создание элементов заказа
    - Логирование операции

---

## Особенности реализации

1. **Работа с API:** Интеграция с Ozon и Wildberries API
2. **PDF генерация:** Использование DomPDF для печати стикеров и документов
3. **Сложная логика сборки:** Управление статусами заказов и их перемещение
   между этапами
4. **Логирование:** Детальное логирование всех операций в разные каналы
5. **Route Model Binding:** Использование для автоматической загрузки моделей
6. **Service Layer:** Вынесение бизнес-логики в сервисы

---

## Роуты

- `GET /warehouse_of_item` - `index` - список товаров на складе
- `GET /warehouse_of_item/new_refunds` - `newRefunds` - обработка возвратов
- `POST /warehouse_of_item/get_storage_barcode_file` - `getStorageBarcodeFile` -
  PDF штрихкодов
- `POST /warehouse_of_item/save_storage/{marketplace_item}` - `saveStorage` -
  сохранение на склад
- `GET /warehouse_of_item/to_pick_list` - `toPickList` - список для подбора
- `GET /warehouse_of_item/to_pick_list_print` - `toPickListPrint` - печать
  списка
- `GET /warehouse_of_item/to_pick/{order}` - `toPick` - сборка заказа
- `POST /warehouse_of_item/labeling/{marketplaceOrder}/{marketplaceOrderItem}` -
  `labeling` - стикеровка
- `POST /warehouse_of_item/done/{marketplaceOrder}` - `done` - завершение сборки
- `POST /warehouse_of_item/to_work/{marketplaceOrder}` - `toWork` - возврат в
  цех
- `GET /warehouse_of_item/add_group` - `addGroup` - форма добавления партией
- `POST /warehouse_of_item/save_group` - `saveGroup` - сохранение партии
