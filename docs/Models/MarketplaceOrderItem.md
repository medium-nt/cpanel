# Модель MarketplaceOrderItem

**Путь:** `app/Models/MarketplaceOrderItem.php`

**Назначение:** Модель для хранения отдельных позиций в заказах из маркетплейсов

**Таблица в БД:** `marketplace_order_items`

## Атрибуты

### Fillable (массово назначаемые)

- `marketplace_order_id` - ID заказа (связь с MarketplaceOrder)
- `marketplace_item_id` - ID товара в маркетплейсе
- `storage_barcode` - Штрихкод на складе
- `shelf_id` - ID полки хранения
- `quantity` - Количество
- `price` - Цена
- `status` - Статус обработки
- `seamstress_id` - ID швеи, назначенной на заказ
- `cutter_id` - ID резчика, назначенного на заказ
- `cutting_completed_at` - Дата/время завершения резки
- `completed_at` - Дата/время выполнения
- `created_at` - Дата/время создания

### Appends (вычисляемые атрибуты)

- `status_name` - Название статуса
- `status_color` - Цвет статуса для отображения

## Связи (Relationships)

### BelongsTo (принадлежит к)

- `marketplaceOrder()` - Заказ, к которому относится элемент
- `shelf()` - Полка хранения (Shelf)

### HasOne (имеет один)

- `item()` - Информация о товаре (MarketplaceItem)
- `seamstress()` - Назначенная швея (User)
- `cutter()` - Назначенный резчик (User)

### HasMany (имеет много)

- `history()` - История изменений элемента заказа (MarketplaceOrderHistory)

## Методы-аксессоры и мутаторы

### Акцессоры (getters)

- `getStatusNameAttribute()` - Возвращает название статуса из модели
  StatusMovement
- `getStatusColorAttribute()` - Возвращает цвет статуса из модели StatusMovement

## Особенности использования

1. **Исполнители:** Каждый элемент заказа может иметь назначенных швею и резчика
2. **Отслеживание этапов:** Поддерживает отслеживание этапов резки (
   `cutting_completed_at`) и полного выполнения (`completed_at`)
3. **Soft Deletes:** Связи с пользователями используют `withTrashed()` для
   отображения даже удаленных пользователей
4. **Статусы:** Использует модель StatusMovement для получения названий и цветов
   статусов
5. **Привязка к складу:** Связь с полками хранения через модель Shelf

## Примеры использования

```php
// Получение элемента с исполнителями
$item = MarketplaceOrderItem::with(['seamstress', 'cutter', 'item'])->find($itemId);

// Получение элементов заказов определенной швеи
$seamstressItems = MarketplaceOrderItem::where('seamstress_id', $userId)->get();

// Фильтрация по статусу
$activeItems = MarketplaceOrderItem::where('status', '!=', StatusMovement::COMPLETED)->get();

// Получение элементов на определенной полке
$shelfItems = MarketplaceOrderItem::where('shelf_id', $shelfId)->get();

// Проверка завершения резки
if ($item->cutting_completed_at) {
    // Резка завершена
}

// Отображение статуса
echo "<span class='badge {$item->status_color}'>{$item->status_name}</span>";

// Получение истории изменений
$history = $item->history()->orderBy('created_at', 'desc')->get();

// Поиск по штрихкоду
$item = MarketplaceOrderItem::where('storage_barcode', $barcode)->first();
```
