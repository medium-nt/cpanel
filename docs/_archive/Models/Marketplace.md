# Модель Marketplace

**Путь:** `app/Models/Marketplace.php`

**Назначение:** Модель для определения маркетплейсов, с которыми работает
система

**Таблица в БД:** `marketplaces`

## Константы

### NAME

Ассоциативный массив, содержащий иконки маркетплейсов:

- `1` - `/icons/ozon.png` - Ozon
- `2` - `/icons/wb.png` - Wildberries

## Особенности использования

1. **Справочник маркетплейсов:** Модель выступает в качестве справочника для
   идентификации маркетплейсов
2. **Конфигурация через константы:** Использует константу NAME для хранения
   путей к иконкам
3. **Интеграция с заказами:** Идентификаторы маркетплейсов используются в
   моделях MarketplaceOrder и MarketplaceSupply

## Поддерживаемые маркетплейсы

На основе констант поддерживаются:

- **Ozon** (ID: 1)
- **Wildberries** (ID: 2)

## Примеры использования

```php
// Получение иконки маркетплейса по ID
$iconPath = Marketplace::NAME[$marketplaceId];

// В моделях заказов используется для отображения иконок
public function getMarketplaceNameAttribute(): string
{
    return Marketplace::NAME[$this->marketplace_id];
}

// Определение маркетплейса по ID
switch($order->marketplace_id) {
    case 1:
        echo "Ozon";
        break;
    case 2:
        echo "Wildberries";
        break;
}

// Проверка заказа на принадлежность к Ozon
if ($order->marketplace_id === 1) {
    // Логика для заказов Ozon
}
```
