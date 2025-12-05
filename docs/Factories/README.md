# Документация Factory классов

Данный раздел содержит подробную документацию для всех Factory классов,
используемых в Laravel приложении для генерации тестовых данных.

## Список Factory классов

| Factory                                                         | Модель                            | Описание                    |
|-----------------------------------------------------------------|-----------------------------------|-----------------------------|
| [MarketplaceItemFactory](./MarketplaceItemFactory.md)           | `App\Models\MarketplaceItem`      | Товар маркетплейса          |
| [MarketplaceOrderFactory](./MarketplaceOrderFactory.md)         | `App\Models\MarketplaceOrder`     | Заказ маркетплейса          |
| [MarketplaceOrderItemFactory](./MarketplaceOrderItemFactory.md) | `App\Models\MarketplaceOrderItem` | Позиция заказа маркетплейса |
| [MaterialFactory](./MaterialFactory.md)                         | `App\Models\Material`             | Материал                    |
| [MotivationFactory](./MotivationFactory.md)                     | `App\Models\Motivation`           | Мотивация сотрудника        |
| [MovementMaterialFactory](./MovementMaterialFactory.md)         | `App\Models\MovementMaterial`     | Движение материала          |
| [OrderFactory](./OrderFactory.md)                               | `App\Models\Order`                | Заказ                       |
| [RateFactory](./RateFactory.md)                                 | `App\Models\Rate`                 | Ставка                      |
| [RoleFactory](./RoleFactory.md)                                 | `App\Models\Role`                 | Роль пользователя           |
| [ScheduleFactory](./ScheduleFactory.md)                         | `App\Models\Schedule`             | График работы               |
| [SettingFactory](./SettingFactory.md)                           | `App\Models\Setting`              | Настройка системы           |
| [SkuFactory](./SkuFactory.md)                                   | `App\Models\Sku`                  | SKU (Stock Keeping Unit)    |
| [StackFactory](./StackFactory.md)                               | `App\Models\Stack`                | Стек (Набор)                |
| [SupplierFactory](./SupplierFactory.md)                         | `App\Models\Supplier`             | Поставщик                   |
| [TransactionFactory](./TransactionFactory.md)                   | `App\Models\Transaction`          | Транзакция                  |
| [TypeMaterialFactory](./TypeMaterialFactory.md)                 | `App\Models\TypeMaterial`         | Тип материала               |
| [UserFactory](./UserFactory.md)                                 | `App\Models\User`                 | Пользователь системы        |

## Общие принципы использования Factory

### Базовое создание

```php
// Создание одной записи
$user = User::factory()->create();

// Создание без сохранения в базу
$user = User::factory()->make();
```

### Массовое создание

```php
// Создание нескольких записей
$users = User::factory()->count(10)->create();
```

### Создание с переопределением полей

```php
// Создание с указанными значениями
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Использование состояний (states)

```php
// Если factory имеет состояния
$user = User::factory()->unverified()->create();
```

### Создание связей

```php
// Создание связанной записи
$order = Order::factory()->create([
    'user_id' => $user->id
]);

// Или автоматическое создание связи
$order = Order::factory()->create(); // Если в factory определен User::factory()
```

### Использование в тестах

```php
// В feature тестах
$user = User::factory()->create();
$response = $this->actingAs($user)->get('/dashboard');
```

## Особенности Factory в этом проекте

1. **Уникальность** - Некоторые поля генерируются с уникальными значениями (
   email, SKU и т.д.)
2. **Предопределенные значения** - Многие поля используют предопределенные
   наборы значений для консистентности
3. **Автоматические связи** - Большинство фабрик автоматически создают связанные
   записи
4. **Бизнес-логика** - Генерируемые значения соответствуют бизнес-логике
   приложения

## Создание новых Factory

Для создания новой фабрики используйте команду:

```bash
php artisan make:factory ModelFactory --model=Model
```

И следуйте существующим паттернам:

- Используйте PHP 8 constructor property promotion
- Определяйте типы возвращаемых значений
- Создавайте полезные состояния при необходимости
- Генерируйте реалистичные тестовые данные

## Лучшая практика

1. Всегда создавайте минимум один factory для каждой модели
2. Используйте factory для генерации тестовых данных, а не ручное создание
3. Создавайте состояния для различных сценариев (published/unpublished,
   active/inactive)
4. Используйте последовательность (sequence) для создания записей с разными
   значениями
5. Тестируйте factory, убедитесь что созданные данные проходят валидацию модели

## Использование в Seeders

Factory также можно использовать в seeders для заполнения базы данных начальными
значениями:

```php
// database/seeders/DatabaseSeeder.php
public function run()
{
    User::factory()->count(50)->create();
    Material::factory()->count(20)->create();
}
```
