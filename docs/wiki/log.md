## [2026-06-20] update | materials-workshop-restriction

- Добавлена система ограничения материалов по цехам: pivot-таблица
  `material_workshop` связывает Material и Workshop (многие-ко-многим)
- `Workshop::allowedMaterials()` — материалы, доступные для заказа в цехе
- `Material::workshops()` — обратная связь
- `AutoOrderService` теперь фильтрует материалы по цеху (только привязанные
  через material_workshop)
- `MovementMaterialToWorkshopController::create()` — dropdown материалов
  фильтруется по цеху пользователя
- `MovementMaterialToWorkshopService::store()` — добавлена проверка доступности
  материала цеху
- `WorkshopController::edit/update` — UI управления привязкой материалов к
  цеху (чекбоксы в `resources/views/workshops/edit.blade.php`)
- Обновлены topics: material-flow.md, shift-system.md, warehouse-operations.md

## [2026-06-20] update | fbo-order-deletion

- Добавлена возможность удаления заказов в FBO-поставках: по одному и массово
  все новые
  (только статус 0, только администраторам, только когда поставка в статусе 0)
- `MarketplaceOrderService::delete()` и `deleteNewOrdersBySupply()` — новые
  методы удаления
- `MarketplaceOrderController::destroyNewBySupply()` — обработчик массового
  удаления
- UI кнопки в `show-ozon-fbo.blade.php` и `show-wb-fbo.blade.php` — "Удалить"
  и "Удалить все новые"
- Каскадное удаление позиций заказов и истории через FK
- Тесты в `MarketplaceOrderDeleteTest.php`
- Обновлены topics: order-lifecycle.md, marketplace-integration.md

## [2026-06-22] update | fbo-order-deletion

- Обновлена документация по удалению заказов в FBO-поставках: добавлены детали
  бизнес-правил, описание методов и файлов
- Уточнено, что удаление работает только для FBO-поставок, FBS не затронуты
- Добавлена информация о политике доступа через
  `MarketplaceSupplyPolicy::deleteOrders()`
- Уточнён механизм каскадного удаления через FK constraints
- Обновлены topics: order-lifecycle.md, marketplace-integration.md

## [2026-06-23] update | fbo-order-detachment

- Добавлена функциональность массовой отвязки не готовых заказов от
  FBO-поставки:
  жёлтая кнопка "Убрать не готовые" (только администраторам, только status=13)
- `MarketplaceOrderService::detachNotReadyOrdersBySupply()` — массовое
  `update()`
  (
  `whereNull('box_id')->where('status', '!=', 0)->update(['supply_id' => null])`)
- `MarketplaceOrderController::detachNotReadyBySupply()` — обработчик с confirm
- **Критерий "не готовых":** заказы без короба (box_id IS NULL) и в работе
  (status != 0)
- Отличие от удаления: заказы остаются в системе, только отвязываются от
  поставки
- **Критерий уточнён:** теперь отвязываются только заказы со `status = 4` ("
  Отшито"),
  а не `status != 0`. Это связано с тем, что ~99% заказов в поставках имеют
  `status=0`
  ("Новый"), прогресс отслеживается по позициям, а order->status остаётся 0
- Кнопки в `show-ozon-fbo.blade.php` и `show-wb-fbo.blade.php`
- Обновлены topics: order-lifecycle.md, marketplace-integration.md, finance.md
- **Уточнение критерия:** `status != 0` → `status = 4` ("Отшито")
- **Причина:** ~99% заказов в поставках имеют `status=0`, прогресс отслеживается
  по позициям

## [2026-06-24] update | new-orders-widget-fix

- Исправлен виджет «Новые задания на пошив» в HomeController.php (строка 75):
- Проблема: widget показывал нули сотрудникам смен, так как фильтровался по
  workshop_id
- Фикс: вызов `MarketplaceOrderItemService::new()` без аргумента (было
  new($workshopScope))
- Новые заказы (status=0) всегда имеют workshop_id=NULL — цех назначается позже
- Остальные виджеты (toWork, toCutting, urgent, cut) продолжают фильтроваться
  корректно по цеху
- Обновлены topics: order-lifecycle.md, shift-system.md

## [2026-06-13] update | wiki-sync-after-regen

- Синхронизирована wiki после регенерации структуры: проверены все 37 моделей,
  23 сервиса, 40 контроллеров, 12 Livewire-компонентов
- Добавлено подробное описание модели Motivation в salary-system.md и
  finance.md: тированные бонусы по объёму работы с разными ставками для
  закройщиков/не-закройщиков
- Детализировано описание MaterialWorkshop в material-flow.md: pivot-таблица для
  связи материалов и цехов (многие-ко-многим), бизнес-правило привязки
- Обновлены topics: salary-system.md, material-flow.md, finance.md

## [2026-06-14] update | settings

- Исправлена работа системных настроек: `SettingController::index()` и `save()`
  теперь строго работают только с глобальными настройками (`workshop_id = NULL`)
- Проблема: ранее цеховые настройки затирали глобальные при чтении, при
  сохранении перезаписывали все записи с одним name
- Решение: добавлен фильтр `->whereNull('workshop_id')` в оба метода
- Multi-workshop архитектура сохранена: цеховые настройки читаются через
  `Setting::getValue()`/`getValues()` с fallback "цеховая → глобальная"
- Обновлён topic: shift-system.md — добавлены бизнес-правила управления
  настройками

## [2026-06-14] update | shift-system

- В календаре смен добавлено третье состояние дня — «выходной» (помимо «смена» и
  «пусто/не заполнено»)
- Миграция: добавлена колонка `workshop_id` в `shift_schedule`, `shift_id` стал
  nullable, изменён UNIQUE на `(workshop_id, date)`
- Новое поведение системы:
    - `canWorkToday()` — ранняя проверка выходных дней: если для цеха сотрудника
      сегодня есть запись-выходный → возвращает false
    - `getMissingScheduleDates()` — выходные записи считаются заполненными (не
      missing)
    - Инвариант: один цех в один день — максимум одна смена (рабочая ИЛИ
      выходной)
- Новые файлы: `StoreShiftScheduleRequest.php`, `ShiftOrDayOff.php` (валидация)
- Обновлено: UI календаря смен — опция «Выходной», подсветка выходных дней
- Обновлён topic: shift-system.md — добавлено описание трех состояний дня,
  нового поведения и инварианта модели
