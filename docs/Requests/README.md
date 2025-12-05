# Документация Form Request классов

Этот раздел содержит подробную документацию по всем классам Form Request в
Laravel приложении. Form Request классы отвечают за валидацию входящих
HTTP-запросов и авторизацию операций.

## Структура документации

Для каждого Form Request класса создан отдельный файл документации со следующими
разделами:

- Назначение класса валидации
- Правила авторизации
- Правила валидации
- Кастомные сообщения об ошибках
- Кастомные атрибуты
- Особенности валидации
- Используемые в контроллерах

## Список Form Request классов

### Материалы и складские операции

- [UpdateMovementMaterialFromSupplierRequest](UpdateMovementMaterialFromSupplierRequest.md) -
  Обновление цен материалов от поставщиков
- [StoreMovementMaterialFromSupplierRequest](StoreMovementMaterialFromSupplierRequest.md) -
  Поступление материалов от поставщиков
- [StoreMovementMaterialToWorkshopRequest](StoreMovementMaterialToWorkshopRequest.md) -
  Перемещение материалов в цех
- [SaveCollectMovementMaterialToWorkshopRequest](SaveCollectMovementMaterialToWorkshopRequest.md) -
  Сбор материалов из цеха
- [SaveWriteOffMovementMaterialToWorkshopRequest](SaveWriteOffMovementMaterialToWorkshopRequest.md) -
  Списание материалов в цехе
- [StoreDefectMaterialToSupplierRequest](StoreDefectMaterialToSupplierRequest.md) -
  Возврат бракованных материалов поставщику
- [SaveDefectMaterialRequest](SaveDefectMaterialRequest.md) - Учет брака и
  остатков
- [StoreRemnantsRequest](StoreRemnantsRequest.md) - Создание записей об остатках
- [StoreInventoryRequest](StoreInventoryRequest.md) - Создание инвентаризации
- [SaveGroupWarehouseOfItemRequest](SaveGroupWarehouseOfItemRequest.md) -
  Групповое добавление товаров на склад

### Маркетплейсы

- [StoreMarketplaceOrderRequest](StoreMarketplaceOrderRequest.md) - Создание
  заказов с маркетплейсов
- [SaveMarketplaceItemRequest](SaveMarketplaceItemRequest.md) - Создание товаров
  для маркетплейсов

### Пользователи и управление

- [StoreUsersRequest](StoreUsersRequest.md) - Создание новых пользователей
- [MotivationUpdateUsersRequest](MotivationUpdateUsersRequest.md) - Обновление
  системы мотивации
- [RateUpdateUsersRequest](RateUpdateUsersRequest.md) - Обновление ставок оплаты

### Финансы и настройки

- [CreateTransactionRequest](CreateTransactionRequest.md) - Создание финансовых
  транзакций
- [SaveSettingRequest](SaveSettingRequest.md) - Сохранение настроек системы

## Общие замечания по реализации

1. **Критические ошибки в некоторых классах**
    - В нескольких классах правила валидации определены некорректно - вместо
      правил указаны сообщения об ошибках
    - Это относится к классам: `SaveWriteOffMovementMaterialToWorkshopRequest`,
      `StoreDefectMaterialToSupplierRequest`,
      `StoreMovementMaterialFromSupplierRequest`,
      `StoreMovementMaterialToWorkshopRequest`,
      `SaveCollectMovementMaterialToWorkshopRequest`,
      `SaveDefectMaterialRequest`, `StoreRemnantsRequest`,
      `SaveMarketplaceItemRequest`
    - Рекомендуется исправить структуру правил для корректной работы валидации

2. **Проблемы с валидацией**
    - В некоторых классах есть несоответствия между правилами и сообщениями об
      ошибках
    - Некоторые правила валидации неполные (например, отсутствует указание
      таблицы для `exists`)

3. **Унификация подхода**
    - Все классы используют `return true` в методе `authorize()`
    - Большинство классов поддерживают массивную валидацию для операций с
      несколькими сущностями

## Рекомендации по улучшению

1. **Исправление некорректных правил валидации**
2. **Добавление более строгой авторизации** для критических операций
3. **Унификация формата сообщений об ошибках**
4. **Добавление type hints** для методов в соответствии с современными
   стандартами PHP
5. **Использование Form Request Validation** во всех контроллерах для повышения
   безопасности
