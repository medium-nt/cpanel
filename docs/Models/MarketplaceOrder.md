# Модель MarketplaceOrder

**Путь:** `app/Models/MarketplaceOrder.php`

**Назначение:** Модель для хранения заказов из маркетплейсов

**Таблица в БД:** `marketplace_orders`

## Атрибуты

### Fillable (массово назначаемые)

- `marketplace_id` - ID маркетплейса (1 - Ozon, 2 - Wildberries)
- `order_id` - ID заказа в маркетплейсе
- `status` - Статус заказа
- `fulfillment_type` - Тип выполнения заказа
- `completed_at` - Дата/время завершения
- `created_at` - Дата/время создания
- `returned_at` - Дата/время возврата

### Appends (вычисляемые атрибуты)

- `marketplace_name` - Иконка маркетплейса
- `status_name` - Название статуса
- `status_color` - Цвет статуса для отображения

## Связи (Relationships)

### HasMany (имеет много)

- `items()` - Элементы заказа (MarketplaceOrderItem)

### BelongsTo (принадлежит к)

- `supply()` - Поставка, к которой относится заказ (MarketplaceSupply)

### HasOne (имеет один)

- `history()` - История изменений заказа (MarketplaceOrderHistory)

## Методы-аксессоры и мутаторы

### Акцессоры (getters)

- `getMarketplaceNameAttribute()` - Возвращает путь к иконке маркетплейса
- `getMarketplaceTitleAttribute()` - Возвращает название маркетплейса (OZON, WB)
- `getStatusNameAttribute()` - Возвращает название статуса из модели
  StatusMovement
- `getMarketplaceStatusLabelAttribute()` - Возвращает HTML-бейдж со статусом
  маркетплейса
- `getStatusColorAttribute()` - Возвращает цвет статуса из модели StatusMovement
- `getCompletedDateAttribute()` - Возвращает дату завершения в формате d/m/Y
- `getReturnedDateAttribute()` - Возвращает дату возврата в формате d/m/Y

## Вспомогательные методы

- `isStickering()` - Проверяет, находится ли заказ на этапе наклейки этикеток (
  статус 5)

## Особенности использования

1. **Интеграция с маркетплейсами:** Поддерживает работу с Ozon и Wildberries
2. **Отображение статусов:** Использует модель StatusMovement для получения
   названий и цветов статусов
3. **Форматирование дат:** Предоставляет отформатированные версии дат для
   отображения
4. **HTML-представление:** Метод `getMarketplaceStatusLabelAttribute()`
   возвращает готовый HTML для отображения статуса
5. **Привязка к поставкам:** Заказы могут быть сгруппированы по поставкам через
   связь с MarketplaceSupply

## Примеры использования

```php
// Получение заказа с элементами
$order = MarketplaceOrder::with('items', 'supply')->find($orderId);

// Фильтрация по маркетплейсу
$ozonOrders = MarketplaceOrder::where('marketplace_id', 1)->get();

// Фильтрация по статусу
$activeOrders = MarketplaceOrder::where('status', '!=', StatusMovement::COMPLETED)->get();

// Получение заказов за период
$orders = MarketplaceOrder::whereBetween('created_at', [$startDate, $endDate])->get();

// Проверка статуса
if ($order->isStickering()) {
    // Логика для этапа наклейки этикеток
}

// Отображение в представлении
echo "<img src='{$order->marketplace_name}'> {$order->marketplace_title}";
echo "<span class='badge {$order->status_color}'>{$order->status_name}</span>";

// Работа с элементами заказа
foreach ($order->items as $item) {
    // Обработка элемента заказа
}
```
