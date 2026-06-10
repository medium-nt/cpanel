# RoleFactory

## Модель

`App\Models\Role` - Роль пользователя

## Генерируемые поля и их типы данных

| Поле   | Тип данных | Описание      | Метод генерации               |
|--------|------------|---------------|-------------------------------|
| `name` | string     | Название роли | `{$roleName}_{$randomNumber}` |

## Особые значения и константы

### Предопределенные типы ролей

- `admin` - Администратор
- `seamstress` - Швея
- `storekeeper` - Кладовщик
- `cutter` - Закройщик
- `otk` - ОТК (Отдел технического контроля)

### Формат генерации имени

Имя роли генерируется в формате: `{тип}_уникальный_5значный_номер`
Примеры:

- `admin_12345`
- `seamstress_67890`
- `storekeeper_54321`

## Состояния (states)

Состояния не определены.

## Связи с другими моделями

Прямые связи не определены в фабрике.

## Примеры использования

### Базовое использование

```php
// Создание случайной роли
$role = Role::factory()->create();

// Получение доступа к полям
echo $role->name; // Например: "admin_12345" или "seamstress_67890"
```

### Создание роли определенного типа

```php
// Создание администратора
$admin = Role::factory()->create(['name' => 'admin_001']);

// Создание швеи
$seamstress = Role::factory()->create(['name' => 'seamstress_001']);
```

### Создание всех типов ролей

```php
// Создание набора базовых ролей
$admin = Role::factory()->create(['name' => 'admin']);
$seamstress = Role::factory()->create(['name' => 'seamstress']);
$storekeeper = Role::factory()->create(['name' => 'storekeeper']);
$cutter = Role::factory()->create(['name' => 'cutter']);
$otk = Role::factory()->create(['name' => 'otk']);
```

### Массовое создание

```php
// Создание 10 ролей
$roles = Role::factory()->count(10)->create();

// Создание ролей каждого типа
$roles = collect([
    Role::factory()->create(),
    Role::factory()->create(),
    Role::factory()->create(),
    Role::factory()->create(),
    Role::factory()->create(),
]);
```

### Создание пользователей с ролями

```php
// Создание роли и пользователя с этой ролью
$adminRole = Role::factory()->create(['name' => 'admin']);
$admin = User::factory()->create(['role_id' => $adminRole->id]);

$seamstressRole = Role::factory()->create(['name' => 'seamstress']);
$seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);
```

### В тестах

```php
// Тестирование API
$role = Role::factory()->create();

$response = $this->getJson("/api/roles/{$role->id}")
    ->assertStatus(200)
    ->assertJson([
        'name' => $role->name
    ]);

// Тестирование с разными ролями
$adminRole = Role::factory()->create(['name' => 'admin']);
$seamstressRole = Role::factory()->create(['name' => 'seamstress']);

$admin = User::factory()->create(['role_id' => $adminRole->id]);
$seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);

$this->actingAs($admin)
    ->get('/admin/dashboard')
    ->assertStatus(200);

$this->actingAs($seamstress)
    ->get('/admin/dashboard')
    ->assertStatus(403);
```

### Комбинирование с другими фабриками

```php
// Создание пользователя с случайной ролью
$role = Role::factory()->create();
$user = User::factory()->create(['role_id' => $role->id]);

// Создание множества пользователей разных ролей
$adminRole = Role::factory()->create(['name' => 'admin']);
$seamstressRole = Role::factory()->create(['name' => 'seamstress']);

$admin = User::factory()->create(['role_id' => $adminRole->id]);
$seamstresses = User::factory()->count(5)->create(['role_id' => $seamstressRole->id]);
```

### Создание с предопределенными ролями

```php
// Создание стандартного набора ролей
$standardRoles = [
    ['name' => 'admin'],
    ['name' => 'seamstress'],
    ['name' => 'storekeeper'],
    ['name' => 'cutter'],
    ['name' => 'otk']
];

foreach ($standardRoles as $roleData) {
    Role::factory()->create($roleData);
}
```

### Создание с уникальными именами

```php
// Создание ролей с гарантией уникальности
$roleTypes = ['admin', 'seamstress', 'storekeeper', 'cutter', 'otk'];
$roles = collect();

foreach ($roleTypes as $index => $type) {
    $roles->push(Role::factory()->create([
        'name' => $type . '_' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)
    ]));
}

// Результат: admin_001, seamstress_002, storekeeper_003 и т.д.
```

### Создание иерархии ролей

```php
// Создание иерархической системы ролей
$roles = [
    ['name' => 'super_admin', 'level' => 5],
    ['name' => 'admin', 'level' => 4],
    ['name' => 'manager', 'level' => 3],
    ['name' => 'seamstress', 'level' => 2],
    ['name' => 'storekeeper', 'level' => 1]
];

// Предполагая, что модель Role имеет поле level
foreach ($roles as $roleData) {
    Role::factory()->create($roleData);
}
```

### Создание с использованием sequence

```php
// Создание ролей с использованием sequence
Role::factory()->count(5)->sequence(
    ['name' => 'admin_001'],
    ['name' => 'seamstress_001'],
    ['name' => 'storekeeper_001'],
    ['name' => 'cutter_001'],
    ['name' => 'otk_001']
)->create();
```
