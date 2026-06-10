# MaterialFactory

## Модель

`App\Models\Material` - Материал

## Генерируемые поля и их типы данных

| Поле      | Тип данных | Описание           | Метод генерации                                |
|-----------|------------|--------------------|------------------------------------------------|
| `title`   | string     | Название материала | `$this->faker->unique()->word()`               |
| `type_id` | integer    | ID типа материала  | `$this->faker->randomElement([1, 2, 3])`       |
| `height`  | integer    | Высота материала   | `$this->faker->randomElement([200, 225, 250])` |
| `unit`    | string     | Единица измерения  | `$this->faker->unique()->word()`               |

## Особые значения и константы

### ID типов материалов

- `1` - Первый тип материала
- `2` - Второй тип материала
- `3` - Третий тип материала

### Возможные значения высоты

- `200`
- `225`
- `250`

### Уникальные поля

- `title` - уникальное название материала
- `unit` - уникальная единица измерения

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание материала
$material = Material::factory()->create();

// Получение доступа к полям
echo $material->title;   // Уникальное слово
echo $material->type_id; // 1, 2 или 3
echo $material->height;  // 200, 225 или 250
echo $material->unit;    // Уникальное слово
```

### Создание с определенными параметрами

```php
// Создание материала с конкретными параметрами
$material = Material::factory()->create([
    'title' => 'Ткань хлопковая',
    'type_id' => 1,
    'height' => 250,
    'unit' => 'метр'
]);

// Создание материала определенного типа
$material = Material::factory()->create([
    'type_id' => 2,
    'title' => 'Синтетический материал'
]);
```

### Создание разных типов материалов

```php
// Создание материалов по типам
$type1Materials = Material::factory()->count(5)->create(['type_id' => 1]);
$type2Materials = Material::factory()->count(5)->create(['type_id' => 2]);
$type3Materials = Material::factory()->count(5)->create(['type_id' => 3]);
```

### Создание с разной высотой

```php
// Создание материалов с разной высотой
$tallMaterials = Material::factory()->count(5)->create(['height' => 250]);
$mediumMaterials = Material::factory()->count(5)->create(['height' => 225]);
$shortMaterials = Material::factory()->count(5)->create(['height' => 200]);
```

### Массовое создание

```php
// Создание 10 материалов
$materials = Material::factory()->count(10)->create();

// Создание материалов с разными высотами в цикле
$materials = collect();
foreach ([200, 225, 250] as $height) {
    $materials->push(...Material::factory()->count(3)->create(['height' => $height]));
}
```

### В тестах

```php
// Тестирование API
$material = Material::factory()->create();

$response = $this->getJson("/api/materials/{$material->id}")
    ->assertStatus(200)
    ->assertJson([
        'title' => $material->title,
        'type_id' => $material->type_id,
        'height' => $material->height,
        'unit' => $material->unit
    ]);

// Тестирование фильтрации по типу
$type = 1;
$type1Materials = Material::factory()->count(3)->create(['type_id' => $type]);

$response = $this->getJson("/api/materials?type={$type}")
    ->assertStatus(200)
    ->assertJsonCount(3);
```

### Комбинирование с другими фабриками

```php
// Создание типа материала и материалов для него
$typeMaterial = TypeMaterialFactory::new()->create();
$materials = Material::factory()->count(5)->create([
    'type_id' => $typeMaterial->id
]);

// Создание движения материала
$material = Material::factory()->create();
$movement = MovementMaterialFactory::new()->create([
    'material_id' => $material->id
]);

// Создание поставщика и связанных материалов
$supplier = SupplierFactory::new()->create();
$materials = Material::factory()->count(3)->create();
foreach ($materials as $material) {
    // Связываем материал с поставщиком через связь в модели
}
```

### Создание с предопределенными названиями

```php
// Создание набора реалистичных материалов
$realMaterials = [
    ['title' => 'Хлопок', 'type_id' => 1, 'height' => 250, 'unit' => 'метр'],
    ['title' => 'Полиэстер', 'type_id' => 2, 'height' => 225, 'unit' => 'метр'],
    ['title' => 'Шелк', 'type_id' => 3, 'height' => 200, 'unit' => 'метр'],
    ['title' => 'Лен', 'type_id' => 1, 'height' => 250, 'unit' => 'метр'],
];

foreach ($realMaterials as $materialData) {
    Material::factory()->create($materialData);
}
```

### Создание материалов с последовательным изменением

```php
// Создание материалов с увеличивающейся высотой
Material::factory()->count(3)->sequence(
    ['height' => 200],
    ['height' => 225],
    ['height' => 250]
)->create();

// Создание материалов разных типов
Material::factory()->count(6)->sequence(
    ['type_id' => 1],
    ['type_id' => 2],
    ['type_id' => 3],
    ['type_id' => 1],
    ['type_id' => 2],
    ['type_id' => 3]
)->create();
```
