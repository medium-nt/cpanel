# Material Flow — Движение материалов

> Last reviewed: 2026-07-16

## Обзор

Материалы поступают от поставщиков на склад, затем отправляются в цеха в виде
рулонов. Система отслеживает каждый рулон от поступления до использования.
Автоматическое пополнение запускается каждые 30 минут при падении запасов ниже
порога (100 единиц). Порог закрытия рулона теперь определяется индивидуально для
каждого материала через поле `minimum_roll_size_for_closure`. Лимит рулонов
ткани
на смену перенесён из константы в настраиваемое поле
`max_fabric_rolls_per_shift`
(глобальное + цеховое override). Двухфлаговая модель состояний материала
(`is_active` / `is_archive`) позволяет гибко управлять видимостью материалов в
разных контекстах (просмотры остатков vs формы заказа).

## Как это работает

### Типы перемещений (TypeMovement)

| Код | Тип                       | Описание                       |
|-----|---------------------------|--------------------------------|
| 1   | Поступление от поставщика | Поставщик → Склад              |
| 2   | Отгрузка на производство  | Склад → Цех                    |
| 3   | Списание по заказу        | Расход на конкретный заказ     |
| 4   | Брак на производстве      | Обнаружен дефект в цеху        |
| 5   | Возврат брака поставщику  | Цех → Поставщик (дефект)       |
| 6   | Списания недостачи с цеха | Потери при производстве        |
| 7   | Остатки на производстве   | Нераспределённые остатки       |
| 8   | Утилизированные остатки   | Безвозвратные потери           |
| 9   | Возврат на склад          | Цех → Склад (неиспользованные) |
| 10  | Ручное списание           | Админ списывает метраж рулона  |

### Фильтрация архивных материалов (02.07.2026)

**Двухфлаговая модель состояний:**

- `is_active` — можно заказать/выбрать в форме (существующее поле)
- `is_archive` — в архиве, скрыт из просмотров остатков (новое поле)

**Scope в Material.php (Этап 2):**

- `Material::notArchived()` — возвращает `is_archive=false`
    - Используется для форм списания/возврата
- `Material::active()` — возвращает `is_active=true` И `is_archive=false`
    - Используется для форм заказа/выбора

**Фильтрация в потоке материалов:**

- **Просмотры остатков** (склад, брак, цех) — фильтруют только
  `is_archive=false`
    - Материалы с `is_archive=true` полностью исключены из расчёта остатков
    - Scope `Material::notArchived()` применяется в:
        - `InventoryService::materialsQuantityByWarehouse()` — остатки на
          складе/браке
        - `InventoryService::materialsQuantityByWorkshopAggregate()` — остатки в
          цеху
- **Формы заказа/выбора** (ЗАКАЗ/ВЫБОР) — фильтруют `Material::active()`:
    - `movements_to_workshop/create` — запрос материалов в цех
    - `marketplace_items/create+edit` — выбор материалов для товара
    - `defect_materials/create` — создание брака
    - `movements_from_supplier/create+edit` — поступление от поставщика
    - `users/edit` — назначение материалов пользователю
    - `workshops/edit` — назначение материалов цеху
    - `AutoOrderService` — автоматическое пополнение
- **Формы списания/возврата** (СПИСАНИЕ/ВОЗВРАТ) — фильтруют
  `Material::notArchived()`:
    - `movements_defect_to_supplier/create` — возврат брака поставщику
    - `write_off_remnants/create` — списание остатков
    - `Livewire MaterialForm` (mount) — форма материалов
- **Админка `/materials`** — показывает все материалы с бейджами статуса

**canArchive — защита перевода в архив:**

- `InventoryService::canArchive(Material)` — проверка возможности перевода
- Условие: `materialInWarehouse() == 0` И `materialInWorkshop() == 0`
- Материал можно перевести в «Архив» ТОЛЬКО из «Нельзя заказать» (
  `is_active=false`)
- Путь статусов: `Активен → Нельзя заказать → Архив`
- `MaterialController::update` — защита через redirect back при нарушении
  условий

**Бизнес-правила:**

- Архивные материалы недоступны для производства и не видны в остатках
- Материалы с `is_active=false` (но `is_archive=false`) видны в остатках, но
  недоступны для заказа в формах
- Материал можно перевести в архив через UI select «Статус → В архиве» в
  `/materials/{id}/edit`
- Маппинг UI select в флаги: Активен=(1,0), Нельзя заказать=(0,0), В архиве=(
  0,1)
- Перевод в архив возможен только когда остатки на складе и в цехе равны 0

### Рулоны (Roll) — жизненный цикл

```
Поступление от поставщика (type_movement=1)
  → Создаётся рулон с уникальным кодом: {type_material_id}-{roll_id_padded}
  → Привязка к смене через shift_id
  → Статус: in_storage

Отгрузка в цех (type_movement=2)
  → Рулон перемещается в цех
  → Статус: shipped_to_workshop → in_workshop

Использование
  → Рулон расходуется на заказы
  → current_quantity уменьшается
  → При full расходе → статус: completed

Завершение рулона:
  → Рядовой сотрудник (швея/закройщик) может завершить рулон только если 
    `current_quantity <= material.minimum_roll_size_for_closure`
  → Если остаток больше порога — закрыть может только кладовщик или админ
  → Проверка порога осуществляется в интерфейсе киоска (`/kiosk/rolls`)
  → Значение по умолчанию: 10 метров (но может отличаться для разных материалов)

Изоляция по сменам
  → Рулоны привязаны к сменам (shift_id)
  → Каждая смена работает только со своими рулонами
  → Админ и кладовщик имеют полный доступ ко всем рулонам

Ручное списание рулона (type_movement=10)
  → Админ может списать метраж с рулона в любой момент (страница рулона)
  → Условия: статус рулона `in_workshop`, `current_quantity > 0`, admin-only
  → Создаётся Order с `type_movement=10`, `status=3`, `comment` из формы
  → MovementMaterial фиксирует списанное количество с привязкой к рулону
  → Транзакция с `lockForUpdate()` защищает от race condition
  → Влияет на `Roll::current_quantity` (учитывается в формуле остатка)
  → Блокирует возврат рулона на склад (как и типы 3,4)
  → Логируется в канал `materials`
```

### Поставщик → Склад

**Связь материалов с поставщиками:**

- Pivot-таблица `material_supplier` связывает материалы и поставщиков
- Каждый материал может иметь несколько поставщиков
- Для каждой пары материал↔поставщик хранится процент недостачи (
  `shortage_percent`)
- **Бизнес-правило:** Недостача хранится справочно (0-100%), пока не применяется
  в
  расчётах
- UI управления: карточка «Поставщики» в `materials/edit.blade.php`
- API: `MaterialSupplierController` (attach, updateShortages, detach)

**MovementMaterialFromSupplierService:**

1. Создаётся заказ с `type_movement=1`
2. Генерируются рулоны с уникальными кодами
3. Можно создать несколько рулонов на одну партию
4. Валидация цены и подтверждение
5. Уведомление через NotificationService (queued, delay=5s) администратору и
   кладовщику (14 массовых рассылок переведены на NotificationService —
   см. user-management.md)

### Склад → Цех

**MovementMaterialToWorkshopService:**

1. Создаётся запрос с `type_movement=2`
2. Роли: швея, закройщик, ОТК могут запросить материалы
3. Запросы привязаны к смене (shift-based)
4. Защита от дубликатов — один запрос на смену
5. Уведомление через NotificationService (queued, delay=5s) о запросе материалов

**Фильтрация материалов по цеху:**

- **MaterialWorkshop** — pivot-таблица для связи материалов и цехов (
  многие-ко-многим)
    - `material_id` — материал
    - `workshop_id` — цех
    - Позволяет привязывать материалы только к определённым цехам
- В интерфейсе создания запроса (
  `MovementMaterialToWorkshopController::create()`) dropdown материалов
  фильтруется по цеху пользователя
- В методе `store()` выполняется проверка доступности материала для цеха
- Метод `Workshop::allowedMaterials()` возвращает материалы, доступные для
  заказа в цехе
- Метод `Material::workshops()` предоставляет обратную связь с цехами
- **Бизнес-правило:** Каждый материал может быть доступен в нескольких цехах

### Автоматическое пополнение

**AutoOrderService** (каждые 30 мин, 7:00–20:00):

1. Проверяет количество материалов в цеху по сменам
2. Если ниже порога (100 единиц) → создаёт автозаказ
3. Защита от дубликатов заказов
4. **Фильтрация по цеху:** теперь фильтрует материалы только через привязку
   `material_workshop` (только привязанные к цеху материалы)
5. Уведомление через NotificationService (queued, delay=5s)

### Списание материалов

**MaterialConsumption** — связывает товары маркетплейса с необходимыми
материалами:

- Определяет сколько и каких материалов нужно для производства одного товара
- Используется при планировании заказов и расчёте себестоимости

**Проверка доступности материалов при взятии заказа:**

- `MarketplaceOrderItemService::hasMaterialsInWorkshop()` — проверяет наличие
  материалов в цехе при взятии заказа (только для ролей seamstress и cutter)
- **Полная матрица «роль × тип материала»:**
  | Тип материала | Константа Material | Когда проверяется |
  |---|---|---|
  | Ткань (Тюль) | TYPE_FABRIC = 1 | Проверяется у закройщиков и швей с кроём;
  ПРОПУСКАЕТСЯ для швей без кроя (`seamstressNotCut` — крой делает
  закройщик) |
  | Аксессуары (тесьма и т.п.) | TYPE_ACCESSORY = 2 | Проверяется ТОЛЬКО у швей
  (`isSeamstress()`); ПРОПУСКАЕТСЯ для закройщиков (тесьму пришивает швея) |
  | Упаковка | TYPE_PACKAGING = 3 | ПРОПУСКАЕТСЯ всегда в этом методе; упаковка
  проверяется/списывается отдельным потоком упаковщика (`KioskService`,
  `StickerPrintingController`) |
- **Принцип:** материал проверяется у той роли, которая с ним физически работает
  (ткань — у кроящего, аксессуары — у шьющей швеи, упаковка — у упаковщика)

**Списание упаковочных материалов:**

- Упаковка (флаер, пакет, флаер-пакет) списывается только через поток упаковщика
- **Все точки списания упаковки теперь используют рулоны текущей смены:**
    1. `MarketplaceOrderItemController::done()` — упаковка при
       стикеровке/упаковке
       (эталон правильного списания)
    2. `StickerPrintingController::processRepack()` — переупаковка товара в ОТК
    3. `StickerPrintingController::processReplace()` — подмена товара
- `KioskService::hasPackagingMaterials(MarketplaceItem, string, Shift)` —
  проверяет
  наличие рулона упаковки в текущей смене (`Roll::STATUS_IN_WORKSHOP`,
  `shift_id = $shift->id`)
-
`KioskService::deductPackagingMaterials(MarketplaceItem, string, string, Shift)` —
создаёт Order с `shift_id`/`workshop_id` и MovementMaterial с `roll_id`
- **Бизнес-правило:** рулоны упаковки привязаны к смене (`shift_id`), НЕ к цеху.
  В цехе на смену — один рулон упаковки (ограничение также в `WorkshopRollScan`)
- При отсутствии рулона или закрытой смены → `RuntimeException` (транзакция
  откатывается, ошибка логируется в канал `materials`)

### Брак и возвраты

**MovementDefectMaterialToSupplierService:**

1. Создаёт заказ с `type_movement=5`
2. Валидирует наличие бракованного материала
3. Уведомление через NotificationService (queued, delay=5s) о возврате брака
4. Возможна корректировка цены

## Ключевые файлы

- `app/Models/Material.php` — модель материалов (название, тип, количество,
  `minimum_roll_size_for_closure` — мин. остаток для закрытия рулона, relation:
  suppliers, константы TYPE_FABRIC/TYPE_ACCESSORY/TYPE_PACKAGING)
- `app/Models/Supplier.php` — модель поставщиков (relation: materials)
- `app/Models/MovementMaterial.php` — модель перемещений
- `app/Models/Roll.php` — модель рулонов (статусы, коды, связь со сменами)
- `app/Models/TypeMovement.php` — типы перемещений
- `app/Models/Setting.php` — модель настроек (чтение через `getValue()` с
  fallback
  "цеховая → глобальная")
- `app/Services/MovementMaterialFromSupplierService.php` — поступление
  (NotificationService уведомление)
- `app/Services/MovementMaterialToWorkshopService.php` — отгрузка в цех (
  проверка доступности материала цеху, NotificationService уведомление)
- `app/Services/MovementDefectMaterialToSupplierService.php` — возврат брака
  (NotificationService уведомление)
- `app/Services/AutoOrderService.php` — автоматическое пополнение (фильтрация по
  цеху, NotificationService уведомление)
- `app/Services/NotificationService.php` — единый шлюз уведомлений (notify
  метод)
- `app/Services/AutoOrderService.php` — автоматическое пополнение (фильтрация по
  цеху)
- `app/Services/WriteOffRemnantService.php` — списание остатков
- `app/Http/Controllers/MaterialSupplierController.php` — управление связями
  материалы↔поставщики (attach, updateShortages, detach)
- `app/Http/Controllers/MovementMaterialToWorkshopController.php` — создание
  запроса материалов (фильтрация по цеху), финальная приёмка поставки с
  проверкой
  лимита рулонов ткани (save_receive)
- `app/Http/Controllers/WorkshopController.php` — управление цехами (привязка
  материалов через чекбоксы, getSettingLabels для UI настроек)
- `app/Http/Controllers\StickerPrintingController.php` — работа с рулонами в
  киоске (scanning, completion, defects) с изоляцией по сменам, включая
  переупаковку (processRepack) и подмену товара (processReplace) с правильным
  списанием упаковки с рулона текущей смены
- `app/Http/Controllers\RollController.php` — ручное списание метража рулона
  (writeOff метод, admin-only через RollPolicy), страница рулона
  `/megatulle/rolls/show/{id}`
- `app/Http/Requests\RollWriteOffRequest.php` — валидация формы списания
  (quantity required|numeric|gt:0, comment nullable|max:1000)
- `app/Policies/RollPolicy.php` — авторизация writeOff (isAdmin() + проверки
  статуса/остатка)
- `app/Livewire/WorkshopRollScan.php` — сканирование рулонов при сборке поставки
  с проверкой лимита рулонов ткани (scanRoll)

## Бизнес-правила

- Рулоны имеют уникальный код: `{type_material_id}-{id padded to N digits}`
- Рулоны привязаны к сменам через `shift_id` — изоляция данных
- Швеи, закройщики, ОТК работают только с рулонами своей смены
- Админ и кладовщик имеют полный доступ ко всем рулонам (без фильтра)
- Один запрос материалов на смену — защита от дубликатов
- Порог автозаказа: 100 единиц при проверке каждые 30 минут
- Все перемещения логируются через модель MovementMaterial
- Telegram-уведомления при ключевых операциях
- **Лимит рулонов ткани на смену (`max_fabric_rolls_per_shift`):**
    - Настраиваемое ограничение на количество рулонов ткани одного вида на смену
    - Читается через
      `Setting::getValue('max_fabric_rolls_per_shift', $workshop_id)`
      с fallback "цеховая → глобальная" (дефолт 99)
    - Применяется в двух точках:
        1. `WorkshopRollScan::scanRoll()` — при сканировании рулонов в поставку
        2. `MovementMaterialToWorkshopController::save_receive()` — при
           финальной
           приёмке поставки
    - Считаются рулоны в статусах `IN_WORKSHOP` + `SHIPPED_TO_WORKSHOP` с тем же
      `material_id` и `shift_id`
    - Для упаковки — строго 1 рулон на смену в цехе (отдельная проверка)
- **Ручное списание рулона (type_movement=10):**
  - Admin-only операция: доступна только администраторам через
    `RollPolicy@writeOff`
  - Условия: рулон в статусе `in_workshop`, `current_quantity > 0`
  - Создаёт Order с `type_movement=10`, `status=3`, `shift_id` из рулона,
    `storekeeper_id = auth()->id()`, `comment` из формы
  - Создаёт MovementMaterial с привязкой к рулону (`roll_id`)
  - Транзакция с `lockForUpdate()` на рулоне защищает от race condition
  - Влияет на `Roll::current_quantity` accessor (учитывается в формуле остатка
    наряду с типами 3,4)
  - Блокирует `RollController::returnToStorage` (после ручного списания вернуть
    на склад нельзя)
  - Логируется в канал `materials`
  - Форма: modal на странице рулона (`/megatulle/rolls/show/{id}`) с полями
    `quantity` (≤ current_quantity) и `comment` (опционально)

## Связанные topics

- [order-lifecycle.md](order-lifecycle.md) — как материалы используются при
  пошиве
- [materials.md](materials.md) — материалы и их свойства (включая порог закрытия
  рулонов)
- [warehouse-operations.md](warehouse-operations.md) — складские операции и
  инвентаризация
- [marketplace-integration.md](marketplace-integration.md) — заказы
  маркетплейсов
- [shift-system.md](shift-system.md) — изоляция рулонов по сменам, права доступа
