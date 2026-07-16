# Materials — Материалы

> Last reviewed: 2026-07-16

## Обзор

Материалы — основа производства. Система управляет поступлением материалов от
поставщиков, их хранением на складе, распределением по цехам и отслеживанием
остатков в виде рулонов. Каждый материал имеет индивидуальные параметры, включая
минимальный остаток для закрытия рулона. Лимит рулонов ткани на смену теперь
настраивается через систему settings (глобально + по цехам), а не захардкожен.

**Двухфлаговая модель состояний материала:**

- `is_active` — можно заказать/выбрать в форме (для будущих точек заказа)
- `is_archive` — в архиве, скрыт из просмотров остатков на складе/браке/цехе

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

### Состояния материалов (двухфлаговая модель)

**Бизнес-смысл (02.07.2026):** Материалы теперь управляются двумя независимыми
флагами состояния для гибкой фильтрации в разных контекстах.

**Флаги:**

- `is_active` (boolean, default true) — «можно заказать/выбрать в форме»
    - Существующее поле, ранее использовалось для отключения материалов
    - В Этапе 2 (будущее) будет фильтровать материалы в формах заказа/выбора
- `is_archive` (boolean, default false) — «в архиве», скрыт из просмотров
  остатков
    - Новое поле, введено 02.07.2026
    - Исключает материал из всех просмотров остатков (склад, брак, цех)

**Карта фильтрации:**

| Контекст использования                | Фильтрация по флагам                             | Видимые материалы            |
|---------------------------------------|--------------------------------------------------|------------------------------|
| Просмотры остатков (склад/брак/цех)   | Только `is_archive=false`                        | Активные + «нельзя заказать» |
| Формы заказа/выбора (Этап 2, будущее) | Оба флага: `is_active=true` И `is_archive=false` | Только активные, не в архиве |
| Админка `/materials`                  | Без фильтрации (все материалы)                   | Все с бейджами статуса       |

**UI (в `/materials/{id}/edit`):**

- ОДИН select «Статус» с 3 опциями:
    1. Активен → (`is_active=true`, `is_archive=false`)
    2. Нельзя заказать → (`is_active=false`, `is_archive=false`)
    3. В архиве → (`is_active=false`, `is_archive=true`)
- В списке `/materials` — 3 цветных бейджа для визуальной индикации

**Scope в Material.php (Этап 2, 02.07.2026):**

- `Material::notArchived()` — возвращает материалы с `is_archive=false`
    - Используется для форм списания/возврата (видны «нельзя заказать», скрыт
      архив)
- `Material::active()` — возвращает материалы с `is_active=true` И
  `is_archive=false`
    - Используется для форм заказа/выбора (только активные, не в архиве)

**Бизнес-правило пути статусов:**
`Активен → Нельзя заказать → Архив`

**canArchive — защита перевода в архив:**

- Новый метод `InventoryService::canArchive(Material)` — проверяет, можно ли
  перевести материал в архив
- Условие: `materialInWarehouse() == 0` И `materialInWorkshop() == 0`
- Материал можно перевести в «Архив» ТОЛЬКО из статуса «Нельзя заказать»
  (`is_active=false`) и при `canArchive() == true`
- `MaterialController::update` — перевод в архив защищён: при нарушении условий
  redirect back с ошибкой

**Бизнес-правила:**

- Материал в архиве (`is_archive=true`) полностью скрыт из просмотров остатков
  на складе (`/megatulle/inventory/`), браке (`defect_warehouse`) и в цеху
  (`/megatulle/inventory/workshop`)
- Материал с `is_active=false` но `is_archive=false` виден в остатках, но
  недоступен для заказа в формах
- Админ видит все материалы в `/materials` независимо от флагов
- Перевод в архив возможен только когда остатки на складе и в цехе равны 0

**Поля модели:**

- `id` — уникальный идентификатор
- `name` — название материала
- `type_id` — тип материала (внешний ключ)
- `quantity` — общее количество на складе
- `is_active` — флаг «можно заказать/выбрать в форме» (boolean, default true)
- `is_archive` — флаг «в архиве», скрыт из просмотров остатков (boolean,
  default false)
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
- Отслеживание остатков `current_quantity` (формула: `initial_quantity − Σ
  MovementMaterial где order.type_movement ∈ [3,4,10]`)
- Статусы жизненного цикла: `in_storage`, `shipped_to_workshop`, `in_workshop`,
  `completed`
- **Типы движения, влияющие на остаток:** 3 (списание по заказу), 4 (брак),
  10 (ручное списание)
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
10. **Ручное списание** (type_movement=10) — админ списывает метраж рулона
    (admin-only, влияет на `Roll::current_quantity`, блокирует возврат на склад)

## Ключевые файлы

- `app/Models/Roll.php` — модель рулонов (accessor `current_quantity` с учётом
  типов движения 3,4,10; проверка блокировки возврата на склад после типов
  3,4,10 в `returnToStorage`)
- `app/Models/TypeMovement.php` — типы перемещений (константа TYPES с ключом
  10='Ручное списание')
- `app/Http/Controllers/RollController.php` — ручное списание метража рулона
  (writeOff метод), страница рулона `/megatulle/rolls/show/{id}`
- `app/Http/Requests/RollWriteOffRequest.php` — валидация формы списания
  (quantity ≤ current_quantity проверка дважды: FormRequest + под lock в
  controller)
- `app/Policies/RollPolicy.php` — авторизация writeOff (isAdmin() + проверки
  статуса/остатка)
- `app/Models/Material.php` — модель материалов (fillable + casts decimal:2,
  relations: suppliers, workshops, константы
  TYPE_FABRIC/TYPE_ACCESSORY/TYPE_PACKAGING)
- `app/Models/Supplier.php` — модель поставщиков (relation: materials)
- `app/Models/Setting.php` — модель настроек (getValue с fallback "цеховая →
  глобальная")
- `app/Services/InventoryService.php` — canArchive(Material) — проверка
  возможности перевода в архив (остатки на складе и в цехе равны 0)
- `app/Services/AutoOrderService.php` — автозаказ с фильтрацией по
  `is_archive=false` (active scope)
- `app/Http/Controllers/MaterialController.php` — валидация store/update:
  required|numeric|min:0; защита перевода в архив через canArchive (update
  метод)
- `app/Http/Controllers/MovementMaterialToWorkshopController.php` — форма
  запроса
  материалов (active scope)
- `app/Http/Controllers/MarketplaceItemController.php` — форма выбора материалов
  для товара (active scope)
- `app/Http/Controllers/DefectMaterialController.php` — форма брака (active
  scope)
- `app/Http/Controllers/MovementMaterialFromSupplierController.php` — форма
  поступления
  от поставщика (active scope)
- `app/Http/Controllers/MovementDefectMaterialToSupplierController.php` — форма
  возврата брака (notArchived scope)
- `app/Http/Controllers/WriteOffRemnantsController.php` — форма списания
  остатков
  (notArchived scope)
- `app/Http/Controllers/UsersController.php` — назначение материалов
  пользователю
  (active scope)
- `app/Http/Controllers/WorkshopController.php` — назначение материалов цеху
  (active scope)
- `app/Livewire/MaterialForm.php` — форма материалов (mount, notArchived scope)
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
- **Ручное списание рулона (type_movement=10):**
    - Admin-only операция: доступна только администраторам
    - Условия: рулон в статусе `in_workshop`, `current_quantity > 0`
    - Влияет на `Roll::current_quantity` accessor (учитывается в формуле
      остатка)
    - Блокирует возврат рулона на склад после списания
    - Логируется в канал `materials`
    - Точка входа: страница рулона `/megatulle/rolls/show/{id}` с modal формой
      списания

## Связанные topics

- [material-flow.md](material-flow.md) — поступление и движение материалов,
  жизненный цикл рулонов, работа с поставщиками
- [shift-system.md](shift-system.md) — изоляция рулонов по сменам, права доступа
- [warehouse-operations.md](warehouse-operations.md) — складские операции и
  инвентаризация
- [order-lifecycle.md](order-lifecycle.md) — использование материалов при пошиве
  заказов
