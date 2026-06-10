# Документация сервисов Laravel приложения

В этом разделе представлена полная документация по всем сервисам приложения,
которые инкапсулируют бизнес-логику.

## Навигация по документации

- [Индекс сервисов](.index.json) - JSON индекс для быстрого поиска

## Основные сервисы

### Управление заказами и маркетплейсы

- [MarketplaceOrderService](MarketplaceOrderService.md) - Управление заказами с
  маркетплейсов
- [MarketplaceOrderItemService](MarketplaceOrderItemService.md) - Управление
  позициями заказов
- [MarketplaceApiService](MarketplaceApiService.md) - API интеграция с Ozon и
  Wildberries
- [MarketplaceItemService](MarketplaceItemService.md) - Управление товарами
  маркетплейсов
- [MarketplaceSupplyService](MarketplaceSupplyService.md) - Управление
  поставками и видео
- [OrderService](OrderService.md) - Общее управление заказами

### Материалы и складские операции

- [MovementMaterialFromSupplierService](MovementMaterialFromSupplierService.md) -
  Поступление материалов
- [MovementMaterialToWorkshopService](MovementMaterialToWorkshopService.md) -
  Перемещение в цех
- [MovementDefectMaterialToSupplierService](MovementDefectMaterialToSupplierService.md) -
  Возврат брака
- [DefectMaterialService](DefectMaterialService.md) - Управление браком
- [WriteOffRemnantService](WriteOffRemnantService.md) - Списание остатков
- [InventoryService](InventoryService.md) - Инвентаризация
- [WarehouseOfItemService](WarehouseOfItemService.md) - Управление товарами на
  складе
- [StackService](StackService.md) - Управление стеком заказов

### Финансы и пользователи

- [TransactionService](TransactionService.md) - Финансовые транзакции
- [UserService](UserService.md) - Управление пользователями
- [ScheduleService](ScheduleService.md) - Управление рабочим графиком

### Интеграции и утилиты

- [TgService](TgService.md) - Telegram уведомления

## Архитектура сервисов

### Принципы проектирования

1. **Single Responsibility** - Каждый сервис отвечает за одну область
   бизнес-логики
2. **Dependency Injection** - Используются внедрения зависимостей через
   конструктор
3. **Thin Controllers** - Контроллеры делегируют бизнес-логику сервисам
4. **Return Types** - Все методы имеют строгую типизацию возвращаемых значений

### Распределение ответственности

| Сервис                  | Основная ответственность            | Ключевые методы                            |
|-------------------------|-------------------------------------|--------------------------------------------|
| MarketplaceOrderService | Управление жизненным циклом заказов | `update`, `setStatus`, `takeOrder`         |
| TransactionService      | Финансовые операции                 | `balance`, `settlement`, `calculateSalary` |
| MovementMaterial*       | Движение материалов                 | `store`, `update`, `delete`                |
| UserService             | Управление пользователями           | `store`, `update`, `delete`                |

### Интеграция между сервисами

- **MarketplaceOrderItemService** использует **TransactionService** для расчетов
- **MovementMaterialToWorkshopService** интегрируется с **TgService** для
  уведомлений
- **MarketplaceApiService** предоставляет данные для **MarketplaceOrderService**
- **UserService** используется в большинстве сервисов для получения данных о
  пользователях

## Статистика

- **Всего сервисов:** 18
- **Сервисы маркетплейсов:** 6
- **Сервисы материалов:** 7
- **Финансовые сервисы:** 2
- **Пользовательские сервисы:** 2
- **Интеграционные сервисы:** 1

## Особенности реализации

### Обработка ошибок

- Большинство сервисов возвращают `boolean` для индикации успеха
- Критические ошибки логируются через `Log::error()`
- Используется механизм DB транзакций для целостности данных

### Валидация

- Сервисы не выполняют валидацию, это делается в FormRequest
- Предполагается, что входные данные уже валидированы

### Тестирование

- Сервисы спроектированы для легкого тестирования
- Минимальные зависимости от Laravel фреймворка
- Чистые бизнес-правила без привязки к HTTP

## Рекомендации по улучшению

1. **Интерфейсы** - Внедрить интерфейсы для сервисов
2. **Events** - Использовать события для loosely-coupled архитектуры
3. **Caching** - Добавить кеширование для частых запросов
4. **Queues** - Вынести тяжелые операции в очереди
5. **DTO** - Использовать Data Transfer Objects для передачи данных

## Как использовать документацию

Каждый файл документации содержит:

- Общее назначение и зону ответственности
- Все публичные методы с параметрами
- Возвращаемые значения и их типы
- Описание бизнес-логики
- Интеграции с другими сервисами
- Особенности и рекомендации

Для начала работы рекомендуем ознакомиться с:

- **MarketplaceOrderService** - ядро бизнес-логики заказов
- **TransactionService** - финансовые расчеты
- **MovementMaterialToWorkshopService** - пример сложной бизнес-логики с
  интеграциями
