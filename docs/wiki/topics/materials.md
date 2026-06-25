# Materials — Материалы

> Last reviewed: 2026-06-25

## Обзор

Материалы — основа производства. Система управляет поступлением материалов от
поставщиков, их хранением на складе, распределением по цехам и отслеживанием
остатков в виде рулонов. Каждый материал имеет индивидуальные параметры, включая
минимальный остаток для закрытия рулона. Лимит рулонов ткани на смену теперь
настраивается через систему settings (глобально + по цехам), а не захардкожен.

## Как это работает

### Материалы (Material)

Основная модель для управления материалами:

- Название и тип материала
- Количество на складе
- Порог закрытия рулона (`minimum_roll_size_for_closure`)
- Привязка к цехам через `material_workshop` pivot-таблицу

**Типы материалов (константы):**

- `TYPE_FABRIC = 1` (Ткань) — проверяется при взятии заказа для закройщиков и
  швей с кроем; пропускается для швей без кроя (`seamstressNotCut`)
- `TYPE_ACCESSORY = 2` (Аксессуары) — тесьма, шнурки и подобные материалы;
  проверяется ТОЛЬКО у швей (`isSeamstress()`); пропускается для закройщиков
  (тесьму пришивает швея, не закройщик)
- `TYPE_PACKAGING = 3` (Упаковка) — проверяется и списывается только в потоке
  упаковщика (`KioskService`, `StickerPrintingController`); исключена из
  проверки
  `hasMaterialsInWorkshop` (швеи и закройщики не используют упаковку)

**Поля модели:**

- `id` — уникальный идентификатор
- `name` — название материала
- `type_id` — тип материала (внешний ключ)
- `quantity` — общее количество на складе
- `minimum_roll_size_for_closure` — минимальный остаток для закрытия рулона (
  decimal, NOT NULL, default 10.00)
- `created_at` / `updated_at` — временные метки

**Привязка к цехам:**

- Pivot-таблица `material_workshop` связь многие-ко-многим
- `Workshop::allowedMaterials()` — материалы, доступные для заказа в цехе
- `Material::workshops()` — обратная связь с цехами
- `WorkshopController::edit/update` — UI управления привязкой через чекбоксы

**Привязка к поставщикам:**

- Pivot-таблица `material_supplier` связь многие-ко-многим
- `Material::suppliers()` — поставщики материала с процентом недостачи
- `Supplier::materials()` — обратная связь с материалами
- Pivot-поля: `id`, `shortage_percent` (decimal 5,2, default 0)
- UI: карточка «Поставщики» в `materials/edit.blade.php` с таблицей и формами
- API: `MaterialSupplierController` — attach, updateShortages, detach
- **Бизнес-правило:** Недостача хранится справочно, пока не применяется в
  расчётах

### Рулоны материалов (Roll)

Материалы хранятся и используются в виде рулонов:

- Уникальный код: `{type_material_id}-{roll_id_padded}`
- Привязка к смене через `shift_id`
- Отслеживание остатков `current_quantity`
- Статусы жизненного цикла: `in_storage`, `shipped_to_workshop`, `in_workshop`,
  `completed`
- **Упаковочные материалы (флаер, пакет, флаер-пакет) также используют рулоны:**
  привязаны к смене (`shift_id`), НЕ к цеху. В цехе на смену — один рулон
  упаковки.
  Списание происходит во всех точках упаковки (стикеровка, переупаковка,
  подмена)
  через `KioskService::deductPackagingMaterials()` с созданием MovementMaterial
  с
  `roll_id`

### Порог закрытия рулона

**Новое бизнес-правило (16.06.2026):**

- Порог перенесен из глобальной настройки `roll_close_min_remaining` в поле
  материала
- `minimum_roll_size_for_closure` — индивидуальный порог для каждого материала
- Значение по умолчанию: 10.00 метров
- Тип данных: `decimal(8,2)`, NOT NULL

**Правила завершения рулона:**

1. **Рядовой сотрудник** (швея/закройщик) может завершить рулон только если:
   `current_quantity <= material.minimum_roll_size_for_closure`

2. **Кладовщик или админ** могут закрыть рулон независимо от остатка

3. **Проверка порога:** осуществляется в интерфейсе киоска (`/kiosk/rolls:89`)
    - При попытке закрытия с остатком > порога показывается alert:
      "Рулон еще не заканчивается!"

4. **Серверная проверка:** отсутствует (как и раньше), только в шаблоне

### Движение материалов

Система поддерживает несколько типов перемещений:

1. **Поступление от поставщика** (type_movement=1)
2. **Отгрузка в цех** (type_movement=2)
3. **Списание по заказу** (type_movement=3)
4. **Брак на производстве** (type_movement=4)
5. **Возврат брака поставщику** (type_movement=5)
6. **Списания недостачи** (type_movement=6)
7. **Остатки на производстве** (type_movement=7)
8. **Утилизированные остатки** (type_movement=8)
9. **Возврат на склад** (type_movement=9)

## Ключевые файлы

- `app/Models/Material.php` — модель материалов (fillable + casts decimal:2,
  relations: suppliers, workshops, константы
  TYPE_FABRIC/TYPE_ACCESSORY/TYPE_PACKAGING)
- `app/Models/Supplier.php` — модель поставщиков (relation: materials)
- `app/Models/Setting.php` — модель настроек (getValue с fallback "цеховая →
  глобальная")
- `app/Http/Controllers/MaterialController.php` — валидация store/update:
  required|numeric|min:0
- `app/Http/Controllers/MaterialSupplierController.php` — управление связями
  материалы↔поставщики (attach, updateShortages, detach)
- `resources/views/materials/edit.blade.php` и `create.blade.php` — поле ввода "
  Мин. остаток для закрытия рулона" + карточка «Поставщики»
- `app/Http/Controllers/StickerPrintingController.php` — использование порога
  при закрытии рулонов
- `app/Http/Controllers/WorkshopController.php` — привязка материалов к цехам,
  getSettingLabels для UI настроек (включая max_fabric_rolls_per_shift)
- `app/Livewire/WorkshopRollScan.php` — сканирование рулонов с проверкой лимита
  тканей (scanRoll)
- `app/Http/Controllers/MovementMaterialToWorkshopController.php` — финальная
  приёмка поставки с проверкой лимита тканей (save_receive)
- `database/seeders/SettingsSeeder.php` — дефолтное значение
  max_fabric_rolls_per_shift = 99
- `app/Http/Requests/SaveSettingRequest.php` — валидация настройки
  (sometimes|integer|min:1)
- `resources/views/settings/index.blade.php` — поле в глобальном UI настроек
-
`database/migrations/2026_06_17_142908_add_minimum_roll_size_for_closure_to_materials_table.php` —
миграция добавления поля
- `database/migrations/2026_06_19_105327_create_material_supplier_table.php` —
  pivot-таблица материалы↔поставщики
- `routes/materials.php` — маршруты для управления связями с поставщиками

## Бизнес-правила

- Каждый материал имеет индивидуальный порог закрытия рулона
  `minimum_roll_size_for_closure`
- Значение по умолчанию: 10.00 метров
- Рулоны привязаны к сменам через `shift_id` — изоляция данных
- Швеи, закройщики, ОТК работают только с рулонами своей смены
- Админ и кладовщик имеют полный доступ ко всем рулонам (без фильтра)
- Автоматическое пополнение при падении запасов ниже 100 единиц
- Все перемещения логируются через модель `MovementMaterial`
- **Лимит рулонов ткани на смену (`max_fabric_rolls_per_shift`):**
    - Перенесён из константы `Material::MAX_FABRIC_ROLLS_PER_SHIFT` в настройки
      системы (25.06.2026)
    - Значение по умолчанию: 99 рулонов на смену (глобальная настройка)
    - Поддерживает цеховое переопределение через карточку цеха
    - Чтение: `Setting::getValue('max_fabric_rolls_per_shift', $workshop_id)`
    - Применяется только к материалам типа `TYPE_FABRIC` (ткань)
    - Проверка в двух точках: сканирование рулонов (WorkshopRollScan) и
      финальная
      приёмка поставки (MovementMaterialToWorkshopController)

## Связанные topics

- [material-flow.md](material-flow.md) — поступление и движение материалов,
  жизненный цикл рулонов, работа с поставщиками
- [shift-system.md](shift-system.md) — изоляция рулонов по сменам, права доступа
- [warehouse-operations.md](warehouse-operations.md) — складские операции и
  инвентаризация
- [order-lifecycle.md](order-lifecycle.md) — использование материалов при пошиве
  заказов
