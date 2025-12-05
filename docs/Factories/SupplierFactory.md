# SupplierFactory

## Модель

`App\Models\Supplier` - Поставщик

## Генерируемые поля и их типы данных

| Поле      | Тип данных | Описание                     | Метод генерации                     |
|-----------|------------|------------------------------|-------------------------------------|
| `title`   | string     | Название компании поставщика | `$this->faker->unique()->company()` |
| `phone`   | string     | Телефон поставщика           | `$this->faker->phoneNumber()`       |
| `address` | string     | Адрес поставщика             | `$this->faker->address()`           |

## Особые значения и константы

### Уникальные поля

- `title` - уникальное название компании

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание поставщика
$supplier = Supplier::factory()->create();

// Получение доступа к полям
echo $supplier->title;   // Уникальное название компании
echo $supplier->phone;   // Случайный номер телефона
echo $supplier->address; // Случайный адрес
```

### Создание с определенными параметрами

```php
// Создание поставщика с конкретными данными
$supplier = Supplier::factory()->create([
    'title' => 'ТекстильПром',
    'phone' => '+7 (495) 123-45-67',
    'address' => 'г. Москва, ул. Ткачей, д. 10'
]);

// Создание с частичными данными
$supplier = Supplier::factory()->create([
    'title' => 'ХлопокИнвест'
]);
```

### Создание поставщиков разных типов

```php
// Поставщик тканей
$fabricSupplier = Supplier::factory()->create([
    'title' => 'Тканевая компания №1',
    'address' => 'г. Иваново, пр. Ленина, д. 25'
]);

// Поставщик фурнитуры
$accessorySupplier = Supplier::factory()->create([
    'title' => 'ФурнитураПро',
    'address' => 'г. Санкт-Петербург, ул. Садовая, д. 15'
]);

// Поставщик оборудования
$equipmentSupplier = Supplier::factory()->create([
    'title' => 'ТекстильМаш',
    'address' => 'г. Москва, ул. Промышленная, д. 5'
]);
```

### Массовое создание

```php
// Создание 10 поставщиков
$suppliers = Supplier::factory()->count(10)->create();

// Создание 5 поставщиков с использованием sequence
Supplier::factory()->count(5)->sequence(
    ['title' => 'ТекстильПлюс', 'address' => 'г. Москва'],
    ['title' => 'Ивановотекс', 'address' => 'г. Иваново'],
    ['title' => 'Швейная армия', 'address' => 'г. Санкт-Петербург'],
    ['title' => 'Ткани России', 'address' => 'г. Нижний Новгород'],
    ['title' => 'Фурнитурщик', 'address' => 'г. Екатеринбург']
)->create();
```

### В тестах

```php
// Тестирование API
$supplier = Supplier::factory()->create();

$response = $this->getJson("/api/suppliers/{$supplier->id}")
    ->assertStatus(200)
    ->assertJson([
        'title' => $supplier->title,
        'phone' => $supplier->phone,
        'address' => $supplier->address
    ]);

// Тестирование поиска
$response = $this->getJson("/api/suppliers?search={$supplier->title}")
    ->assertStatus(200)
    ->assertJsonFragment(['title' => $supplier->title]);
```

### Комбинирование с другими фабриками

```php
// Создание поставщика и связанных с ним материалов
$supplier = Supplier::factory()->create();
$materials = Material::factory()->count(5)->create();

foreach ($materials as $material) {
    // Предполагая связь через pivot таблицу
    $supplier->materials()->attach($material->id, [
        'price' => rand(100, 1000),
        'min_order' => rand(10, 100)
    ]);
}

// Создание заказа от поставщика
$supplier = Supplier::factory()->create();
$order = Order::factory()->create([
    'supplier_id' => $supplier->id
]);

// Создание движения материалов от поставщика
$supplier = Supplier::factory()->create();
$material = Material::factory()->create();
$movement = MovementMaterial::factory()->create([
    'material_id' => $material->id
    // Предполагая, что заказ связан с поставщиком
]);
```

### Создание с реалистичными данными

```php
// Создание реалистичных российских поставщиков
$realSuppliers = [
    [
        'title' => 'ОАО "Ивановский текстиль"',
        'phone' => '+7 (4932) 45-67-89',
        'address' => '153000, г. Иваново, ул. Ф. Энгельса, д. 17'
    ],
    [
        'title' => 'ООО "Текстильная мануфактура"',
        'phone' => '+7 (812) 123-45-67',
        'address' => '190000, г. Санкт-Петербург, наб. канала Грибоедова, д. 34'
    ],
    [
        'title' => 'ЗАО "Русский хлопок"',
        'phone' => '+7 (495) 987-65-43',
        'address' => '109004, г. Москва, ул. Земляной вал, д. 50'
    ],
    [
        'title' => 'ООО "Ткани Поволжья"',
        'phone' => '+7 (843) 234-56-78',
        'address' => '420034, г. Казань, ул. Декабристов, д. 2'
    ],
    [
        'title' => 'ИП "ТекстильТрейд"',
        'phone' => '+7 (343) 345-67-89',
        'address' => '620014, г. Екатеринбург, ул. Малышева, д. 30'
    ]
];

foreach ($realSuppliers as $supplierData) {
    Supplier::factory()->create($supplierData);
}
```

### Создание по категориям

```php
// Поставщики тканей
$fabricSuppliers = Supplier::factory()->count(3)->sequence(
    ['title' => 'ТканиЛюкс', 'address' => 'г. Москва'],
    ['title' => 'ШёлкМастер', 'address' => 'г. Санкт-Петербург'],
    ['title' => 'ХлопокГрупп', 'address' => 'г. Краснодар']
)->create();

// Поставщики фурнитуры
$accessorySuppliers = Supplier::factory()->count(3)->sequence(
    ['title' => 'ФурнитураПро', 'address' => 'г. Москва'],
    ['title' => 'ЗастежкаПлюс', 'address' => 'г. Новосибирск'],
    ['title' => 'НиткаТорг', 'address' => 'г. Самара']
)->create();

// Поставщики оборудования
$equipmentSuppliers = Supplier::factory()->count(2)->sequence(
    ['title' => 'ШвейноеОборудование', 'address' => 'г. Екатеринбург'],
    ['title' => 'ТекстильМаш', 'address' => 'г. Челябинск']
)->create();
```

### Создание с разными форматами телефонов

```php
// Создание поставщиков с разными форматами телефонов
Supplier::factory()->count(3)->sequence(
    ['phone' => '+7 (495) 123-45-67'],     // Московский
    ['phone' => '+7 (812) 234-56-78'],     // Петербургский
    ['phone' => '8 (800) 345-67-89']       // Бесплатный
)->create();
```

### Создание для международных поставщиков

```php
// Создание международных поставщиков
$internationalSuppliers = [
    [
        'title' => 'Textile Import GmbH',
        'phone' => '+49 30 12345678',
        'address' => 'Germany, Berlin, Friedrichstraße 123'
    ],
    [
        'title' => 'Silk Road Trading',
        'phone' => '+86 21 8765 4321',
        'address' => 'China, Shanghai, Nanjing Road 456'
    ],
    [
        'title' => 'Cotton USA Inc.',
        'phone' => '+1 212-555-0123',
        'address' => 'USA, New York, 5th Avenue 789'
    ]
];

foreach ($internationalSuppliers as $supplierData) {
    Supplier::factory()->create($supplierData);
}
```

### Создание с дополнительными полями (если они есть)

```php
// Создание поставщиков с дополнительной информацией
$suppliers = Supplier::factory()->count(5)->create()->each(function ($supplier) {
    // Предполагая, что модель имеет дополнительные поля
    $supplier->update([
        'email' => strtolower(str_replace(' ', '.', $supplier->title)) . '@example.com',
        'website' => 'https://' . strtolower(str_replace([' ', '"', 'ОАО', 'ООО', 'ЗАО'], '', $supplier->title)) . '.ru',
        'inn' => $this->faker->numerify('##########'),
        'kpp' => $this->faker->numerify('#########')
    ]);
});
```

### Создание для иерархии

```php
// Создание головных компаний и филиалов
$headCompanies = Supplier::factory()->count(3)->create();
foreach ($headCompanies as $headCompany) {
    // Создаем филиалы
    Supplier::factory()->count(2)->create([
        'title' => $headCompany->title . ' - филиал',
        'address' => $this->faker->city() . ', ' . $this->faker->streetAddress()
    ]);
}
```
