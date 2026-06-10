# SettingFactory

## Модель

`App\Models\Setting` - Настройка системы

## Генерируемые поля и их типы данных

| Поле    | Тип данных | Описание           | Метод генерации        |
|---------|------------|--------------------|------------------------|
| `name`  | string     | Название настройки | `$this->faker->word()` |
| `value` | string     | Значение настройки | `$this->faker->word()` |

## Особые значения и константы

### Генерация данных

- `name` генерируется как случайное слово
- `value` генерируется как случайное слово

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание настройки
$setting = Setting::factory()->create();

// Получение доступа к полям
echo $setting->name;  // Случайное слово
echo $setting->value; // Случайное слово
```

### Создание с определенными параметрами

```php
// Создание настройки с определенными значениями
$setting = Setting::factory()->create([
    'name' => 'site_name',
    'value' => 'My Application'
]);

// Создание настройки с числовым значением
$setting = Setting::factory()->create([
    'name' => 'max_upload_size',
    'value' => '10485760' // 10MB в байтах
]);

// Создание boolean настройки
$setting = Setting::factory()->create([
    'name' => 'maintenance_mode',
    'value' => 'false'
]);
```

### Создание набора настроек

```php
// Создание стандартных настроек приложения
$settings = [
    ['name' => 'app_name', 'value' => 'My Laravel App'],
    ['name' => 'app_url', 'value' => 'https://example.com'],
    ['name' => 'default_timezone', 'value' => 'UTC'],
    ['name' => 'date_format', 'value' => 'Y-m-d H:i:s'],
    ['name' => 'items_per_page', 'value' => '25']
];

foreach ($settings as $settingData) {
    Setting::factory()->create($settingData);
}
```

### Массовое создание

```php
// Создание 10 настроек
$settings = Setting::factory()->count(10)->create();

// Создание настроек с использованием sequence
Setting::factory()->count(3)->sequence(
    ['name' => 'setting_one', 'value' => 'value_one'],
    ['name' => 'setting_two', 'value' => 'value_two'],
    ['name' => 'setting_three', 'value' => 'value_three']
)->create();
```

### В тестах

```php
// Тестирование API
$setting = Setting::factory()->create();

$response = $this->getJson("/api/settings/{$setting->id}")
    ->assertStatus(200)
    ->assertJson([
        'name' => $setting->name,
        'value' => $setting->value
    ]);

// Тестирование получения всех настроек
$settings = Setting::factory()->count(5)->create();

$response = $this->getJson('/api/settings')
    ->assertStatus(200)
    ->assertJsonCount(5);
```

### Создание настроек для разных типов данных

```php
// Строковые настройки
$stringSettings = Setting::factory()->count(2)->sequence(
    ['name' => 'site_title', 'value' => 'Welcome'],
    ['name' => 'contact_email', 'value' => 'admin@example.com']
)->create();

// Числовые настройки
$numericSettings = Setting::factory()->count(2)->sequence(
    ['name' => 'page_size', 'value' => '20'],
    ['name' => 'session_timeout', 'value' => '3600']
)->create();

// Boolean настройки
$booleanSettings = Setting::factory()->count(2)->sequence(
    ['name' => 'enable_notifications', 'value' => 'true'],
    ['name' => 'debug_mode', 'value' => 'false']
)->create();
```

### Создание для конфигурации приложения

```php
// Настройки для конфигурации
$configSettings = [
    // Параметры приложения
    ['name' => 'app_name', 'value' => 'Production System'],
    ['name' => 'app_version', 'value' => '1.0.0'],
    ['name' => 'app_env', 'value' => 'production'],

    // Параметры базы данных
    ['name' => 'db_connection', 'value' => 'mysql'],
    ['name' => 'db_host', 'value' => 'localhost'],
    ['name' => 'db_port', 'value' => '3306'],

    // Параметры кэша
    ['name' => 'cache_driver', 'value' => 'redis'],
    ['name' => 'cache_ttl', 'value' => '3600'],

    // Параметры почты
    ['name' => 'mail_driver', 'value' => 'smtp'],
    ['name' => 'mail_host', 'value' => 'smtp.gmail.com'],
    ['name' => 'mail_port', 'value' => '587'],
];

foreach ($configSettings as $settingData) {
    Setting::factory()->create($settingData);
}
```

### Создание для бизнес-логики

```php
// Бизнес-настройки
$businessSettings = [
    ['name' => 'working_hours_start', 'value' => '09:00'],
    ['name' => 'working_hours_end', 'value' => '18:00'],
    ['name' => 'lunch_break_start', 'value' => '13:00'],
    ['name' => 'lunch_break_end', 'value' => '14:00'],
    ['name' => 'working_days', 'value' => '1,2,3,4,5'], // Пн-Пт
    ['name' => 'currency', 'value' => 'RUB'],
    ['name' => 'tax_rate', 'value' => '0.20'],
    ['name' => 'min_order_amount', 'value' => '1000'],
    ['name' => 'free_delivery_threshold', 'value' => '5000'],
];

foreach ($businessSettings as $settingData) {
    Setting::factory()->create($settingData);
}
```

### Создание для функциональности

```php
// Настройки функциональности
$featureSettings = [
    ['name' => 'enable_registration', 'value' => 'true'],
    ['name' => 'enable_social_login', 'value' => 'false'],
    ['name' => 'enable_email_verification', 'value' => 'true'],
    ['name' => 'enable_two_factor_auth', 'value' => 'false'],
    ['name' => 'max_login_attempts', 'value' => '5'],
    ['name' => 'lockout_duration', 'value' => '900'], // 15 минут
];

foreach ($featureSettings as $settingData) {
    Setting::factory()->create($settingData);
}
```

### Создание с уникальными именами

```php
// Создание настроек с уникальными именами
$prefix = 'myapp_';
for ($i = 1; $i <= 10; $i++) {
    Setting::factory()->create([
        'name' => $prefix . 'setting_' . $i,
        'value' => 'value_' . $i
    ]);
}
```

### Создание для локализации

```php
// Настройки локализации
$localeSettings = [
    ['name' => 'default_locale', 'value' => 'ru'],
    ['name' => 'fallback_locale', 'value' => 'en'],
    ['name' => 'timezone', 'value' => 'Europe/Moscow'],
    ['name' => 'date_format', 'value' => 'd.m.Y'],
    ['name' => 'time_format', 'value' => 'H:i:s'],
    ['name' => 'decimal_separator', 'value' => ','],
    ['name' => 'thousands_separator', 'value' => ' '],
];

foreach ($localeSettings as $settingData) {
    Setting::factory()->create($settingData);
}
```
