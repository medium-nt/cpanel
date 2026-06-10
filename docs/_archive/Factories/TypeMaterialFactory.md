# TypeMaterialFactory

## Модель

`App\Models\TypeMaterial` - Тип материала

## Генерируемые поля и их типы данных

| Поле         | Тип данных | Описание                | Метод генерации                  |
|--------------|------------|-------------------------|----------------------------------|
| `title`      | string     | Название типа материала | `$this->faker->unique()->word()` |
| `created_at` | datetime   | Время создания          | `Carbon::now()`                  |
| `updated_at` | datetime   | Время обновления        | `Carbon::now()`                  |

## Особые значения и константы

### Уникальные поля

- `title` - уникальное слово

### Временные метки

- `created_at` и `updated_at` устанавливаются в текущее время создания записи

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание типа материала
$typeMaterial = TypeMaterial::factory()->create();

// Получение доступа к полям
echo $typeMaterial->title;      // Уникальное слово
echo $typeMaterial->created_at; // Текущее время
echo $typeMaterial->updated_at; // Текущее время
```

### Создание с определенными параметрами

```php
// Создание типа с конкретным названием
$typeMaterial = TypeMaterial::factory()->create([
    'title' => 'Хлопок'
]);

// Создание с предопределенным временем
$typeMaterial = TypeMaterial::factory()->create([
    'title' => 'Шелк',
    'created_at' => '2024-01-01 00:00:00',
    'updated_at' => '2024-01-01 00:00:00'
]);
```

### Создание стандартных типов материалов

```php
// Создание базовых типов текстильных материалов
$basicTypes = [
    ['title' => 'Хлопок'],
    ['title' => 'Шелк'],
    ['title' => 'Лен'],
    ['title' => 'Шерсть'],
    ['title' => 'Полиэстер'],
    ['title' => 'Вискоза'],
    ['title' => 'Ацетат'],
    ['title' => 'Нейлон']
];

foreach ($basicTypes as $typeData) {
    TypeMaterial::factory()->create($typeData);
}
```

### Создание специализированных типов

```php
// Создание типов для разных категорий материалов

// Натуральные волокна
$natural = [
    ['title' => 'Хлопок'],
    ['title' => 'Шелк'],
    ['title' => 'Лен'],
    ['title' => 'Шерсть'],
    ['title' => 'Джут'],
    ['title' => 'Конопля']
];

// Синтетические волокна
$synthetic = [
    ['title' => 'Полиэстер'],
    ['title' => 'Нейлон'],
    ['title' => 'Акрил'],
    ['title' => 'Спандекс'],
    ['title' => 'Полиамид']
];

// Искусственные волокна
$artificial = [
    ['title' => 'Вискоза'],
    ['title' => 'Ацетат'],
    ['title' => 'Модал'],
    ['title' => 'Лиоцелл']
];

foreach ($natural as $type) {
    TypeMaterial::factory()->create($type);
}
foreach ($synthetic as $type) {
    TypeMaterial::factory()->create($type);
}
foreach ($artificial as $type) {
    TypeMaterial::factory()->create($type);
}
```

### Массовое создание

```php
// Создание 10 типов материалов
$types = TypeMaterial::factory()->count(10)->create();

// Создание с использованием sequence
TypeMaterial::factory()->count(5)->sequence(
    ['title' => 'Ткань'],
    ['title' => 'Кружево'],
    ['title' => 'Фурнитура'],
    ['title' => 'Нитки'],
    ['title' => 'Утеплитель']
)->create();
```

### В тестах

```php
// Тестирование API
$type = TypeMaterial::factory()->create();

$response = $this->getJson("/api/type-materials/{$type->id}")
    ->assertStatus(200)
    ->assertJson([
        'title' => $type->title,
        'created_at' => $type->created_at->toISOString(),
        'updated_at' => $type->updated_at->toISOString()
    ]);

// Тестирование получения всех типов
TypeMaterial::factory()->count(5)->create();

$response = $this->getJson('/api/type-materials')
    ->assertStatus(200)
    ->assertJsonCount(5);
```

### Комбинирование с другими фабриками

```php
// Создание типа и материалов этого типа
$type = TypeMaterial::factory()->create(['title' => 'Хлопок']);
$materials = Material::factory()->count(5)->create(['type_id' => $type->id]);

// Создание иерархии типов
$parentType = TypeMaterial::factory()->create(['title' => 'Ткани']);
$childTypes = TypeMaterial::factory()->count(3)->create();
// Предполагая, что модель имеет parent_id
$childTypes->each(function ($childType) use ($parentType) {
    $childType->update(['parent_id' => $parentType->id]);
});
```

### Создание с временными метками для истории

```php
// Создание типов с разным временем создания
TypeMaterial::factory()->create([
    'title' => 'Легендарная ткань',
    'created_at' => now()->subYears(10),
    'updated_at' => now()->subYears(10)
]);

TypeMaterial::factory()->create([
    'title' => 'Современный материал',
    'created_at' => now()->subMonths(6),
    'updated_at' => now()->subMonths(2)
]);

TypeMaterial::factory()->create([
    'title' => 'Новейшая разработка',
    'created_at' => now()->subDays(10),
    'updated_at' => now()
]);
```

### Создание для категоризации

```php
// Создание категорий типов материалов
$categories = [
    // Основа
    ['title' => 'Основа'],
    ['title' => 'Уток'],

    // Отделка
    ['title' => 'Кружево'],
    ['title' => 'Вышивка'],
    ['title' => 'Аппликация'],

    // Фурнитура
    ['title' => 'Пуговицы'],
    ['title' => 'Молнии'],
    ['title' => 'Шнурки'],
    ['title' => 'Пряжка'],

    // Утеплители
    ['title' => 'Синтепон'],
    ['title' => 'Холлофайбер'],
    ['title' => 'Пух']
];

foreach ($categories as $category) {
    TypeMaterial::factory()->create($category);
}
```

### Создание с уникальными префиксами

```php
// Создание типов с префиксами для лучшей организации
$prefixes = ['fabric_', 'trim_', 'accessory_', 'lining_'];

foreach ($prefixes as $prefix) {
    for ($i = 1; $i <= 3; $i++) {
        TypeMaterial::factory()->create([
            'title' => $prefix . 'type_' . $i
        ]);
    }
}
```

### Создание с многословными названиями

```php
// Переопределение фабрики для генерации фраз вместо слов
$typeMaterial = TypeMaterial::factory()->make();
$typeMaterial->title = $this->faker->words(2, true); // Генерирует 2 слова
$typeMaterial->save();

// Или создание напрямую
TypeMaterial::factory()->create([
    'title' => $this->faker->words(3, true)
]);
```

### Создание с локализацией

```php
// Создание типов на русском языке
$russianTypes = [
    'Хлопок',
    'Лён',
    'Шёлк',
    'Шерсть',
    'Ситец',
    'Сатин',
    'Бязь',
    'Твид',
    'Вельвет',
    'Бархат'
];

foreach ($russianTypes as $type) {
    TypeMaterial::factory()->create(['title' => $type]);
}
```

### Создание для тестирования Soft Deletes (если поддерживается)

```php
// Создание типа с мягким удалением
$type = TypeMaterial::factory()->create();
$type->delete(); // Мягкое удаление

// Проверка, что тип удален
$this->assertSoftDeleted('type_materials', [
    'id' => $type->id
]);
```
