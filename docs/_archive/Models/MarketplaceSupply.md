# Модель MarketplaceSupply

**Путь:** `app/Models/MarketplaceSupply.php`

**Назначение:** Модель для хранения информации о поставках из маркетплейсов

**Таблица в БД:** `marketplace_supplies`

## Атрибуты

### Fillable (массово назначаемые)

- `supply_id` - ID поставки в маркетплейсе
- `marketplace_id` - ID маркетплейса (1 - Ozon, 2 - Wildberries)
- `status` - Статус поставки
- `completed_at` - Дата/время завершения
- `video` - Путь к видеофайлу (видеоотчет)

## Методы-аксессоры и мутаторы

### Акцессоры (getters)

- `getMarketplaceNameAttribute()` - Возвращает путь к иконке маркетплейса из
  модели Marketplace

## Связи (Relationships)

### HasMany (имеет много)

- `marketplace_orders()` - Заказы, входящие в поставку (MarketplaceOrder)

## Особенности использования

1. **Группировка заказов:** Поставки служат для группировки заказов из
   маркетплейсов
2. **Видеофиксация:** Поддерживает хранение видеофайлов (вероятно, для
   отчетности)
3. **Интеграция с маркетплейсами:** Работает с теми же маркетплейсами, что и
   заказы
4. **Статусы:** Имеет поле status для отслеживания состояния поставки

## Примеры использования

```php
// Получение поставки с заказами
$supply = MarketplaceSupply::with('marketplace_orders')->find($supplyId);

// Получение всех поставок определенного маркетплейса
$ozonSupplies = MarketplaceSupply::where('marketplace_id', 1)->get();

// Фильтрация по статусу
$activeSupplies = MarketplaceSupply::where('status', 'active')->get();

// Получение заказов в поставке
foreach ($supply->marketplace_orders as $order) {
    // Обработка заказа
}

// Отображение иконки маркетплейса
echo "<img src='{$supply->marketplace_name}'>";

// Поиск поставки по ID из маркетплейса
$supply = MarketplaceSupply::where('supply_id', $marketplaceSupplyId)
    ->where('marketplace_id', $marketplaceId)
    ->first();

// Проверка наличия видео
if ($supply->video) {
    // Обработка видео
}
```
