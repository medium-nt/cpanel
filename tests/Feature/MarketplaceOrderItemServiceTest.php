<?php

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Shift;
use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getOrdersGroupedByMaterial groups orders by material title for seamstress', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

    // Создаем пользователей
    $seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id]);

    // Создаем marketplace item и заказы
    $marketplaceItem = MarketplaceItem::factory()->create([
        'width' => 300,
        'height' => 200,
    ]);

    $marketplaceOrder = MarketplaceOrder::factory()->create();

    // Назначаем швею
    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'marketplace_item_id' => $marketplaceItem->id,
        'quantity' => 5,
        'status' => 4,
        'seamstress_id' => $seamstress->id,
        'cutter_id' => null,
        'workshop_id' => null,
    ]);

    // Создаем второй заказ с таким же материалом
    $secondOrderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'marketplace_item_id' => $marketplaceItem->id,
        'quantity' => 3,
        'status' => 4,
        'seamstress_id' => $seamstress->id,
        'cutter_id' => null,
        'workshop_id' => null,
    ]);

    // Создаем заказ с другим материалом
    $otherMarketplaceItem = MarketplaceItem::factory()->create([
        'title' => 'Другой материал',
        'width' => 400,
        'height' => 300,
    ]);

    $otherOrderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'marketplace_item_id' => $otherMarketplaceItem->id,
        'quantity' => 2,
        'status' => 4,
        'seamstress_id' => $seamstress->id,
        'cutter_id' => null,
        'workshop_id' => null,
    ]);

    $service = new MarketplaceOrderItemService;
    $result = $service->getOrdersGroupedByMaterial($seamstress);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(2);
    expect($result->keys()->toArray())->toContain($marketplaceItem->title);
    expect($result->keys()->toArray())->toContain('Другой материал');

    // Проверяем, что заказы отсортированы по ширине/высоте
    $group1 = $result->get($marketplaceItem->title);
    expect($group1->first()->item->width)->toBe(300);
    expect($group1->first()->item->height)->toBe(200);
});

test('getOrdersGroupedByMaterial groups orders by material title for cutter', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

    // Создаем пользователей
    $cutter = User::factory()->create(['role_id' => $cutterRole->id]);

    // Создаем marketplace item и заказы
    $marketplaceItem = MarketplaceItem::factory()->create([
        'width' => 300,
        'height' => 200,
    ]);

    $marketplaceOrder = MarketplaceOrder::factory()->create();

    // Назначаем раскройщика
    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'marketplace_item_id' => $marketplaceItem->id,
        'status' => 7, // Статус "в раскрое"
        'cutter_id' => $cutter->id,
        'seamstress_id' => 0,
        'workshop_id' => null,
    ]);

    $service = new MarketplaceOrderItemService;
    $result = $service->getOrdersGroupedByMaterial($cutter);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(1);
    expect($result->keys()->first())->toBe($marketplaceItem->title);
});

test('checkTimeoutOrderItem returns false for order not yet timeout', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем marketplace item
    $marketplaceItem = MarketplaceItem::factory()->create([
        'width' => 300,
        'height' => 200,
    ]);

    $marketplaceOrder = MarketplaceOrder::factory()->create();

    // Создаем заказ с начальным временем в прошлом, но не превышающее таймаут
    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'marketplace_item_id' => $marketplaceItem->id,
        'quantity' => 5,
        'status' => 4,
        'workshop_id' => null,
        'seamstress_id' => 0,
        'cutter_id' => null,
        'started_at' => now()->subHours(1),
    ]);

    // Устанавливаем настройки таймаута (в минутах)
    DB::table('settings')->insertOrIgnore([
        ['name' => 'timeout_300', 'value' => '120', 'workshop_id' => null],
    ]);

    // Авторизуем пользователя
    auth()->login($admin);

    $service = new MarketplaceOrderItemService;
    $result = $service->checkTimeoutOrderItem($orderItem);

    // Выходим из системы
    auth()->logout();

    expect($result)->toBeFalse();
});

test('checkTimeoutOrderItem returns true for order that has timeout', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем marketplace item
    $marketplaceItem = MarketplaceItem::factory()->create([
        'width' => 300,
        'height' => 200,
    ]);

    $marketplaceOrder = MarketplaceOrder::factory()->create();

    // Создаем заказ с начальным временем в далеком прошлом
    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'marketplace_item_id' => $marketplaceItem->id,
        'quantity' => 5,
        'status' => 4,
        'workshop_id' => null,
        'seamstress_id' => 0,
        'cutter_id' => null,
        'started_at' => now()->subHours(5),
    ]);

    // Устанавливаем короткий таймаут (30 минут)
    DB::table('settings')->insertOrIgnore([
        ['name' => 'timeout_300', 'value' => '30', 'workshop_id' => null],
    ]);

    // Авторизуем пользователя
    auth()->login($admin);

    $service = new MarketplaceOrderItemService;
    $result = $service->checkTimeoutOrderItem($orderItem);

    // Выходим из системы
    auth()->logout();

    expect($result)->toBeTrue();
});

test('new returns count of orders with status 0', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем только заказы со статусом 0
    MarketplaceOrderItem::factory()->create(['status' => 0, 'quantity' => 5]);
    MarketplaceOrderItem::factory()->create(['status' => 0, 'quantity' => 3]);

    // Тестируем без авторизации (администратор)
    $result = MarketplaceOrderItemService::new();

    expect($result)->toBe(8); // 5 + 3 = 8
});

test('toWork returns count of orders with status 4 for seamstress', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

    // Создаем пользователей
    $seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем только заказы со статусом 4 для швеи
    MarketplaceOrderItem::factory()->create(['status' => 4, 'quantity' => 5, 'seamstress_id' => $seamstress->id]);
    MarketplaceOrderItem::factory()->create(['status' => 4, 'quantity' => 3, 'seamstress_id' => $seamstress->id]);

    // Тестируем для швеи
    auth()->login($seamstress);
    $result = MarketplaceOrderItemService::toWork();
    auth()->logout();

    expect($result)->toBe(8); // 5 + 3 = 8
});

test('toWork returns count of orders with status 4 for admin', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    // Создаем пользователя
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем только заказы со статусом 4
    MarketplaceOrderItem::factory()->create(['status' => 4, 'quantity' => 5]);
    MarketplaceOrderItem::factory()->create(['status' => 4, 'quantity' => 3]);

    // Тестируем для администратора
    auth()->login($admin);
    $result = MarketplaceOrderItemService::toWork();
    auth()->logout();

    expect($result)->toBe(8); // 5 + 3 = 8
});

test('toCutting returns count of orders with status 7 for cutter', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

    // Создаем пользователей
    $cutter = User::factory()->create(['role_id' => $cutterRole->id]);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем только заказы со статусом 7 для раскройщика
    MarketplaceOrderItem::factory()->create(['status' => 7, 'quantity' => 5, 'cutter_id' => $cutter->id]);
    MarketplaceOrderItem::factory()->create(['status' => 7, 'quantity' => 3, 'cutter_id' => $cutter->id]);

    // Тестируем для раскройщика
    auth()->login($cutter);
    $result = MarketplaceOrderItemService::toCutting();
    auth()->logout();

    expect($result)->toBe(8); // 5 + 3 = 8
});

test('toCutting returns count of orders with status 7 for admin', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    // Создаем пользователя
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаем только заказы со статусом 7
    MarketplaceOrderItem::factory()->create(['status' => 7, 'quantity' => 5]);
    MarketplaceOrderItem::factory()->create(['status' => 7, 'quantity' => 3]);

    // Тестируем для администратора
    auth()->login($admin);
    $result = MarketplaceOrderItemService::toCutting();
    auth()->logout();

    expect($result)->toBe(8); // 5 + 3 = 8
});

test('cancelToSeamstress returns error for invalid status', function () {
    // Создаем роли
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    // Создаем пользователя
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceOrder = MarketplaceOrder::factory()->create();

    // Создаем заказ с неверным статусом
    $testOrderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceOrder->id,
        'status' => 1, // Неверный статус
        'seamstress_id' => 0,
        'cutter_id' => null,
        'workshop_id' => null,
    ]);

    // Авторизуем пользователя
    auth()->login($admin);

    $result = MarketplaceOrderItemService::cancelToSeamstress($testOrderItem);

    // Выходим из системы
    auth()->logout();

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Заказ с таким статусом не может быть отменен');
});

test('hasMaterialsInWorkshop пропускает упаковку: закройщик берёт заказ без упаковочного рулона', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id]);

    $shift = Shift::factory()->create();
    $cutter->shifts()->attach($shift->id, ['effective_from' => now()->subDay()->toDateString()]);

    // Товар с расходом: ткань + упаковка
    $fabric = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $packaging = Material::factory()->create(['type_id' => Material::TYPE_PACKAGING]);
    $item = MarketplaceItem::factory()->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($fabric)->withQuantity(2)->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($packaging)->withQuantity(1)->create();

    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_item_id' => $item->id,
        'quantity' => 3,
    ]);

    // Рулон ткани в цехе текущей смены — хватает (требуется 2 * 3 = 6).
    Roll::factory()->inWorkshop()->create([
        'material_id' => $fabric->id,
        'shift_id' => $shift->id,
        'initial_quantity' => 100,
    ]);
    // Рулон упаковки намеренно НЕ создаём — раньше именно это ронило проверку.

    auth()->login($cutter);

    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'hasMaterialsInWorkshop');

    expect($method->invoke(null, $orderItem))->toBeTrue();
});

test('hasMaterialsInWorkshop всё ещё ловит нехватку ткани — пропуск упаковки не отключил проверку', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id]);

    $shift = Shift::factory()->create();
    $cutter->shifts()->attach($shift->id, ['effective_from' => now()->subDay()->toDateString()]);

    // Только ткань в расходе, рулона ткани в цехе нет.
    $fabric = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $item = MarketplaceItem::factory()->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($fabric)->withQuantity(2)->create();

    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_item_id' => $item->id,
        'quantity' => 3,
    ]);

    auth()->login($cutter);

    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'hasMaterialsInWorkshop');

    expect($method->invoke(null, $orderItem))->toBeFalse();
});
