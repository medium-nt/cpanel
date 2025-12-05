# Модель MovementMaterial

**Путь:** `app/Models/MovementMaterial.php`

**Назначение:** Модель для отслеживания движения материалов на складе (приход,
расход, перемещение)

**Таблица в БД:** `movement_materials`

## Атрибуты

### Fillable (массово назначаемые)

- `material_id` - ID материала (связь с Material)
- `quantity` - Количество материала
- `ordered_quantity` - Заказанное количество
- `price` - Цена за единицу
- `order_id` - ID связанного заказа (если применимо)

## Связи (Relationships)

### BelongsTo (принадлежит к)

- `material()` - Материал (Material)

## Особенности использования

1. **Учет движения:** Модель используется для отслеживания всех операций с
   материалами на складе
2. **Количественный учет:** Хранит как фактическое количество (`quantity`), так
   и заказанное (`ordered_quantity`)
3. **Ценовой учет:** Поддерживает хранение цены за единицу материала
4. **Привязка к заказам:** Может быть связана с заказами через поле `order_id`
5. **Инвентаризация:** Используется для учета остатков и движения материалов

## Примеры использования

```php
// Получение всех движений материала
$movements = MovementMaterial::where('material_id', $materialId)->get();

// Получение движения с информацией о материале
$movements = MovementMaterial::with('material')->get();

// Расчет общего количества материала
$totalQuantity = MovementMaterial::where('material_id', $materialId)->sum('quantity');

// Создание записи о поступлении материала
$movement = MovementMaterial::create([
    'material_id' => $materialId,
    'quantity' => 100,
    'ordered_quantity' => 100,
    'price' => 50.00,
    'order_id' => $orderId
]);

// Получение движений за период
$periodMovements = MovementMaterial::whereBetween('created_at', [$startDate, $endDate])
    ->with('material')
    ->get();

// Фильтрация по заказу
$orderMovements = MovementMaterial::where('order_id', $orderId)->get();

// Проверка доступного количества
$available = MovementMaterial::where('material_id', $materialId)
    ->where('quantity', '>', 0)
    ->sum('quantity');

// Получение материалов с отрицательным балансом (расход без прихода)
$negativeBalance = MovementMaterial::select('material_id')
    ->selectRaw('SUM(quantity) as total')
    ->groupBy('material_id')
    ->havingRaw('SUM(quantity) < 0')
    ->with('material')
    ->get();

// Журнал движений для инвентаризации
$inventoryLog = MovementMaterial::with(['material' => function($query) {
        $query->withTrashed();
    }])
    ->orderBy('created_at', 'desc')
    ->get();
```
