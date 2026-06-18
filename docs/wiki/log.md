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
