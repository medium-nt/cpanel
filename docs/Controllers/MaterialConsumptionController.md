# MaterialConsumptionController

**Путь:** `app/Http/Controllers/MaterialConsumptionController.php`

## Общее назначение

Контроллер для управления расходом материалов. Реализует только операцию
удаления записей о расходе материалов.

## Зависимости и сервисы

- `App\Models\MaterialConsumption` - модель расхода материалов

## Список методов

### destroy(MaterialConsumption $materialConsumption)

- **Описание:** Удаление записи о расходе материала
- **URL:** `/material_consumptions/{materialConsumption}`
- **Метод:** DELETE
- **Параметры:**
    - `$materialConsumption` (MaterialConsumption) - удаляемая запись о
      расходе (route model binding)
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Редирект:**
  `route('marketplace_items.edit', ['marketplace_item' => $materialConsumption->item->id])`
- **Связанные данные:** Использует связь `item` для получения ID связанного
  элемента маркетплейса

## Работа с моделями данных

- **MaterialConsumption:** Основная модель для работы с расходом материалов
- **Item (MarketplaceItem):** Связанная модель элемента маркетплейса через
  отношение `item`

## Особенности реализации

- Контроллер реализует только операцию удаления
- После удаления происходит перенаправление на страницу редактирования
  связанного элемента маркетплейса
- Не включает методы для создания или просмотра расходов

## Права доступа

- Контроллер не использует Gate или middleware для проверки прав доступа
- Предполагается использование глобальных middleware для аутентификации

## Валидация

- Отсутствует явная валидация
- Используется route model binding для автоматической загрузки и проверки
  существования модели
