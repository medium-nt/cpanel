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
