# Документация Seeders

Этот раздел содержит подробную документацию для всех классов Seeder в Laravel
приложении.

## Назначение Seeders

Seeders используются для первоначального наполнения базы данных тестовыми или
начальными данными. Они обеспечивают:

- Быстрое развертывание системы с необходимыми данными
- Согласованность данных между разными окружениями
- Тестовые сценарии для разработки и тестирования

## Порядок выполнения

Seeders должны выполняться в строгом порядке, указанном в `DatabaseSeeder`:

1. `RoleSeeder` - создание ролей (нет зависимостей)
2. `UserSeeder` - создание пользователей (требует роли)
3. `TypeMaterialSeeder` - типы материалов (нет зависимостей)
4. `MaterialSeeder` - материалы (требует типы)
5. `SupplierSeeder` - поставщики
6. `ShelfSeeder` - полки склада
7. `OrderSeeder` - заказы (требует пользователей и поставщиков)
8. `MovementMaterialSeeder` - движения материалов (требует заказы и материалы)
9. `MarketplaceItemSeeder` - товары маркетплейсов (требует материалы)
10. `TransactionSeeder` - транзакции (требует пользователей)
11. `ScheduleSeeder` - график работы (требует пользователей)
12. `SettingsSeeder` - настройки системы (нет зависимостей)

## Запуск Seeders

Для выполнения всех seeders:

```bash
php artisan db:seed
```

Для выполнения конкретного seeder:

```bash
php artisan db:seed --class=RoleSeeder
```

## Особенности

- Некоторые seeders используют метод `firstOrCreate` для предотвращения
  дублирования данных
- Пароли тестовых пользователей intentionally просты для удобства разработки
- Настройки API ключей в SettingsSeeder оставлены пустыми и должны быть
  заполнены администратором

## Список документации

- [DatabaseSeeder](DatabaseSeeder.md) - главный seeder
- [MarketplaceItemSeeder](MarketplaceItemSeeder.md) - товары маркетплейсов
- [MarketplaceOrderItemSeeder](MarketplaceOrderItemSeeder.md) - позиции заказов
- [MarketplaceOrderSeeder](MarketplaceOrderSeeder.md) - заказы маркетплейсов
- [MaterialConsumptionSeeder](MaterialConsumptionSeeder.md) - расход материалов
- [MaterialSeeder](MaterialSeeder.md) - материалы
- [MovementMaterialSeeder](MovementMaterialSeeder.md) - движения материалов
- [OrderSeeder](OrderSeeder.md) - заказы
- [RoleSeeder](RoleSeeder.md) - роли пользователей
- [ScheduleSeeder](ScheduleSeeder.md) - график работы
- [SettingsSeeder](SettingsSeeder.md) - настройки системы
- [ShelfSeeder](ShelfSeeder.md) - полки склада
- [SupplierSeeder](SupplierSeeder.md) - поставщики
- [TransactionSeeder](TransactionSeeder.md) - транзакции
- [TypeMaterialSeeder](TypeMaterialSeeder.md) - типы материалов
- [UserSeeder](UserSeeder.md) - пользователи
