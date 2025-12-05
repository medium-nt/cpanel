# Документация контроллеров Laravel приложения

В этом разделе содержится подробная документация по всем контроллерам
приложения.

## Структура документации

### Базовые контроллеры

- [Controller](Controller.md) - Базовый контроллер
- [HomeController](HomeController.md) - Главный дашборд
- [PageController](PageController.md) - Статические страницы

### Аутентификация и авторизация

- [Auth/ConfirmPasswordController](Auth/ConfirmPasswordController.md) -
  Подтверждение пароля
- [Auth/ForgotPasswordController](Auth/ForgotPasswordController.md) -
  Восстановление пароля
- [Auth/LoginController](Auth/LoginController.md) - Вход в систему
- [Auth/RegisterController](Auth/RegisterController.md) - Регистрация
  пользователей
- [Auth/ResetPasswordController](Auth/ResetPasswordController.md) - Сброс пароля
- [Auth/VerificationController](Auth/VerificationController.md) - Верификация
  email

### Управление пользователями

- [UsersController](UsersController.md) - Управление пользователями
- [ScheduleController](ScheduleController.md) - Рабочее расписание
- [SettingController](SettingController.md) - Настройки системы

### Маркетплейсы

- [MarketplaceApiController](MarketplaceApiController.md) - API интеграция
- [MarketplaceItemController](MarketplaceItemController.md) - Товары
  маркетплейсов
- [MarketplaceOrderController](MarketplaceOrderController.md) - Заказы
  маркетплейсов
- [MarketplaceOrderItemController](MarketplaceOrderItemController.md) - Позиции
  заказов
- [MarketplaceSupplyController](MarketplaceSupplyController.md) - Поставки на
  маркетплейсы

### Материалы и склад

- [MaterialController](MaterialController.md) - Управление материалами
- [MaterialConsumptionController](MaterialConsumptionController.md) - Расход
  материалов
- [DefectMaterialController](DefectMaterialController.md) - Управление браком
- [InventoryController](InventoryController.md) - Инвентаризация
- [MovementDefectMaterialToSupplierController](MovementDefectMaterialToSupplierController.md) -
  Возврат брака
- [MovementMaterialByMarketplaceOrderController](MovementMaterialByMarketplaceOrderController.md) -
  Расход на заказы
- [MovementMaterialFromSupplierController](MovementMaterialFromSupplierController.md) -
  Поступление материалов
- [MovementMaterialToWorkshopController](MovementMaterialToWorkshopController.md) -
  Перемещение в цех
- [ShelfController](ShelfController.md) - Управление полками
- [SupplierController](SupplierController.md) - Управление поставщиками
- [WarehouseOfItemController](WarehouseOfItemController.md) - Складские операции
- [WriteOffRemnantsController](WriteOffRemnantsController.md) - Списание
  остатков

### Финансы и транзакции

- [TransactionController](TransactionController.md) - Финансовые операции

### Интеграции и утилиты

- [StickerPrintingController](StickerPrintingController.md) - Печать стикеров
- [TelegramController](TelegramController.md) - Telegram бот

## Общее количество контроллеров: 29

### Примечания

- Все контроллеры используют Laravel Gates для проверки прав доступа
- Большинство контроллеров требуют открытую рабочую смену
- Контроллеры следуют паттерну "тонкий контроллер" с вынесением бизнес-логики в
  сервисы

## Обновление документации

Документация создана 2025-12-04. При изменении контроллеров необходимо обновлять
соответствующие файлы документации.
