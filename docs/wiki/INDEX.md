# cpanel — Project Wiki Index

> Generated: 2026-06-13 21:09 | Models: 37 | Services: 23 | Controllers: 40 |
> Livewire: 12

## Quick Orientation
Warehouse/inventory management with Ozon/WB marketplace integration.
PHP 8.2, Laravel 11, Livewire 3, AdminLTE, Tailwind 3, Pest.

## Models (37)

| Model                   | Table                         | Key Relations                                                 | Traits                              |
|-------------------------|-------------------------------|---------------------------------------------------------------|-------------------------------------|
| InventoryCheck          | `inventory_checks`            | items                                                         |                                     |
| InventoryCheckItem      | `inventory_check_items`       | expectedShelf, foundedShelf, marketplaceOrderItem             |                                     |
| Marketplace             | `marketplaces`                |                                                               |                                     |
| MarketplaceItem         | `marketplace_items`           | marketplaceOrderItem, sku, consumption +1                     | HasFactory                          |
| MarketplaceOrder        | `marketplace_orders`          | items, supply, box +1                                         | HasFactory                          |
| MarketplaceOrderHistory | `marketplace_order_history`   | item, order                                                   |                                     |
| MarketplaceOrderItem    | `marketplace_order_items`     | marketplaceOrder, workshop, item +6                           | HasFactory                          |
| MarketplaceSupply       | `marketplace_supplies`        |                                                               | HasFactory                          |
| MarketplaceWarehouse    | `marketplace_warehouses`      |                                                               |                                     |
| Material                | `materials`                   | type, rolls, movementMaterials +3                             | HasFactory, SoftDeletes             |
| MaterialConsumption     | `material_consumptions`       | item, material                                                | HasFactory                          |
| MaterialWorkshop        | `material_workshop`           | material, workshop                                            | HasFactory                          |
| Motivation              | `motivations`                 |                                                               | HasFactory                          |
| MovementMaterial        | `movement_materials`          | material, roll, order                                         | HasFactory                          |
| Order                   | `orders`                      | workshop, user, seamstress +6                                 | HasFactory                          |
| OzonFboDraftSupplyItem  | `ozon_fbo_draft_supply_items` | supply, skuRecord                                             |                                     |
| ProductSticker          | `product_stickers`            |                                                               |                                     |
| Rate                    | `rates`                       | material                                                      | HasFactory                          |
| Role                    | `roles`                       |                                                               | HasFactory                          |
| Roll                    | `rolls`                       | supplyOrder, material, shift +3                               | HasFactory                          |
| Schedule                | `schedules`                   | user, shift                                                   | HasFactory                          |
| Setting                 | `settings`                    | workshop                                                      | HasFactory                          |
| Shelf                   | `shelves`                     |                                                               |                                     |
| Shift                   | `shifts`                      | workshop, users, rolls                                        | HasFactory                          |
| ShiftSchedule           | `shift_schedule`              | shift                                                         | HasFactory                          |
| Sku                     | `skus`                        | item                                                          | HasFactory                          |
| Stack                   | `stacks`                      |                                                               | HasFactory                          |
| StatusMovement          | `status_movements`            |                                                               |                                     |
| Supplier                | `suppliers`                   | orders                                                        | HasFactory, SoftDeletes             |
| SupplyBox               | `supply_boxes`                | supply, orders                                                |                                     |
| Tariff                  | `tariffs`                     | userTariff, material                                          | HasFactory                          |
| Transaction             | `transactions`                | user                                                          | HasFactory                          |
| TypeMaterial            | `type_materials`              |                                                               | HasFactory                          |
| TypeMovement            | `type_movements`              |                                                               |                                     |
| User                    | `users`                       | role, marketplaceOrderItems, marketplaceOrderItemsByCutter +4 | HasFactory, Notifiable, SoftDeletes |
| UserTariff              | `user_tariffs`                | user, tariffs                                                 | HasFactory                          |
| Workshop                | `workshops`                   | shifts, orders, marketplaceOrderItems +3                      | HasFactory                          |

## Services (23)

| Service                                 | Methods | Dependencies |
|-----------------------------------------|---------|--------------|
| ActionAccrualService                    | 2       | —            |
| AutoOrderService                        | 2       | —            |
| DefectMaterialService                   | 3       | —            |
| ExcelOrderImportService                 | 3       | —            |
| InventoryService                        | 12      | —            |
| MarketplaceApiService                   | 59      | —            |
| MarketplaceOrderItemService             | 25      | —            |
| MarketplaceOrderService                 | 9       | —            |
| MarketplaceSupplyService                | 3       | —            |
| MovementDefectMaterialToSupplierService | 2       | —            |
| MovementMaterialFromSupplierService     | 2       | —            |
| MovementMaterialToWorkshopService       | 7       | —            |
| OrderService                            | 1       | —            |
| RollService                             | 3       | —            |
| ScheduleService                         | 9       | —            |
| ShiftService                            | 7       | —            |
| StackService                            | 4       | —            |
| StickerService                          | 2       | —            |
| TgService                               | 1       | —            |
| TransactionService                      | 12      | —            |
| UserService                             | 13      | —            |
| WarehouseOfItemService                  | 8       | —            |
| WriteOffRemnantService                  | 1       | —            |

## Controllers (40)

| Controller                                   | Key Methods                                                                                        |
|----------------------------------------------|----------------------------------------------------------------------------------------------------|
| ConfirmPasswordController                    | showConfirmForm, confirm, redirectPath                                                             |
| ForgotPasswordController                     | showLinkRequestForm, sendResetLinkEmail, broker                                                    |
| LoginController                              | showLoginForm, login, username, logout, redirectPath +2                                            |
| RegisterController                           | showRegistrationForm, register, redirectPath                                                       |
| ResetPasswordController                      | showResetForm, reset, broker, redirectPath                                                         |
| VerificationController                       | show, verify, resend, redirectPath                                                                 |
| BarcodeSearchController                      | index, findItemByBarcode                                                                           |
| Controller                                   | authorize, authorizeForUser, authorizeResource, validateWith, validate +1                          |
| DefectMaterialController                     | index, create, store, approve_reject, pick_up +3                                                   |
| HomeController                               | index                                                                                              |
| InventoryController                          | byWarehouse, byWorkshop, inventoryChecks, show, create +2                                          |
| MarketplaceApiController                     | checkSkuz, checkDuplicateSkuz, uploadingNewProducts, uploadingCancelledProducts, getBarcodeFile +2 |
| MarketplaceItemController                    | index, create, store, edit, update +1                                                              |
| MarketplaceOrderController                   | index, create, store, edit, update +6                                                              |
| MarketplaceOrderItemController               | index, show, done, cancel, labeling +7                                                             |
| MarketplaceSupplyController                  | index, show, linkWbFbo, loadFboGoods, editFbo +22                                                  |
| MaterialConsumptionController                | destroy                                                                                            |
| MaterialController                           | index, create, store, edit, update +1                                                              |
| MaterialMovementController                   | index                                                                                              |
| MovementDefectMaterialToSupplierController   | index, create, store                                                                               |
| MovementMaterialByMarketplaceOrderController | index                                                                                              |
| MovementMaterialFromSupplierController       | index, create, store, show, edit +2                                                                |
| MovementMaterialToWorkshopController         | index, create, store, collect, write_off +6                                                        |
| OzonReturnsController                        | index, refreshBarcode, giveoutInfo, products                                                       |
| PageController                               | index                                                                                              |
| ProductStickerController                     | index, create, store, edit, update +1                                                              |
| RollController                               | index, show, printRoll, printOrder, returnToStorage +1                                             |
| ScheduleController                           | changeDate                                                                                         |
| SettingController                            | index, save, test, syncWarehousesOzon, syncWarehousesWb +1                                         |
| ShelfController                              | index, create, store, edit, update +1                                                              |
| ShiftController                              | index, create, store, show, update +8                                                              |
| StickerPrintingController                    | enterKiosk, index, openCloseWorkShift, openCloseWorkShiftAdmin, kiosk +18                          |
| SupplierController                           | index, create, store, edit, update +1                                                              |
| SupplyBoxController                          | index, markAssembled, store, destroy, show +4                                                      |
| TelegramController                           | webhook                                                                                            |
| TransactionController                        | index, create, store, destroy, createPayoutSalary +3                                               |
| UsersController                              | index, create, store, edit, update +7                                                              |
| WarehouseOfItemController                    | index, exportExcel, inspection, newRefunds, getStorageBarcodeFile +13                              |
| WorkshopController                           | index, create, store, edit, update +1                                                              |
| WriteOffRemnantsController                   | index, create, store                                                                               |

## Livewire (12)

| Component          | View                            | Properties                                                       |
|--------------------|---------------------------------|------------------------------------------------------------------|
| BoxOrderScanner    | `livewire.box-order-scanner`    | $box, $scanCode, $statusMessage, $statusType                     |
| DefectMaterialScan | `livewire.defect-material-scan` | $scanCode, $statusMessage, $statusType, $statusClass             |
| ExcelOrderImport   | `livewire.excel-order-import`   | $step, $excelFile, $fileHeaders, $columnMap                      |
| InventoryCheckScan | `livewire.inventory-check-scan` | $inventory, $selectedShelfId, $scanCode, $statusMessage          |
| MaterialForm       | `livewire.material-form`        | $selectedMaterialId, $materials, $orderedQuantity, $maxQuantity  |
| OzonFboItemSearch  | `livewire.ozon-fbo-item-search` | $supply, $search, $results, $quantity                            |
| ShelfChange        | `livewire.shelf-change`         | $selectedShelfId, $scanCode, $statusMessage, $statusType         |
| StatusChangeScan   | `livewire.status-change-scan`   | $fromStatus, $toStatus, $pageTitle, $scanCode                    |
| StickerTapeImport  | `livewire.sticker-tape-import`  | $step, $excelFile, $fileHeaders, $columnMap                      |
| SupplyOrderList    | `livewire.supply-order-list`    | $supplyId                                                        |
| SupplyOrderSearch  | `livewire.supply-order-search`  | $orderId, $supply, $message, $messageType                        |
| WorkshopRollScan   | `livewire.workshop-roll-scan`   | $order, $scanCode, $requestedMaterialId, $requestedMaterialTitle |

## Route Groups

- `/megatulle/` + `auth` — базовые авторизованные роуты (users, shifts,
  transactions, workshops)
- `/megatulle/` + `auth` + `require_open_shift` — операционные роуты (materials,
  orders, marketplace, inventory)
- `routes/api.php` — webhooks (Telegram)
- `routes/kiosk.php` — интерфейс киоска
- `routes/console.php` — cron-задачи (подробнее
  в [maps/schedule.md](maps/schedule.md))

## Topic Guides (business logic)
- [Order Lifecycle](topics/order-lifecycle.md) — статусная машина заказов
- [Material Flow](topics/material-flow.md) — движение материалов
- [Marketplace Integration](topics/marketplace-integration.md) — Ozon/WB API
- [Shift System](topics/shift-system.md) — смены и цеха
- [Salary System](topics/salary-system.md) — начисления и тарифы
- [Warehouse Operations](topics/warehouse-operations.md) — склад, стеллажи,
  стикеры
- [Finance](topics/finance.md) — транзакции, мотивация

## Detailed Maps
- [Models](maps/models.md) — полные fillable, casts, relationships
- [Services](maps/services.md) — все методы с сигнатурами
- [Controllers](maps/controllers.md) — все методы контроллеров
- [Routes](maps/routes.md) — все роут-файлы
- [Livewire](maps/livewire.md) — компоненты и их views
- [Schedule](maps/schedule.md) — cron-задачи
