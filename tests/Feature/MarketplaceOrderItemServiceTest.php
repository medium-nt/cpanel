<?php

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceWarehouse;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Очищаем order_items — тесты проверки счётчиков статусов требуют точного числа записей.
beforeEach(function () {
    $this->cleanTables = ['marketplace_order_items'];
    $this->cleanTables();
});

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
    /** @var \Illuminate\Support\Collection $group1 */
    $group1 = $result->get($marketplaceItem->title);
    /** @var \App\Models\MarketplaceOrderItem $first */
    $first = $group1->first();
    expect($first->item->width)->toBe(300);
    expect($first->item->height)->toBe(200);
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

test('hasMaterialsInWorkshop пропускает аксессуары для закройщика: тесьму пришивает швея', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id]);

    $shift = Shift::factory()->create();
    $cutter->shifts()->attach($shift->id, ['effective_from' => now()->subDay()->toDateString()]);

    // Товар с расходом: ткань + аксессуары (тесьма)
    $fabric = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $accessory = Material::factory()->create(['type_id' => Material::TYPE_ACCESSORY]);
    $item = MarketplaceItem::factory()->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($fabric)->withQuantity(2)->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($accessory)->withQuantity(1)->create();

    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_item_id' => $item->id,
        'quantity' => 3,
    ]);

    // Рулон ткани в цехе текущей смены — хватает.
    Roll::factory()->inWorkshop()->create([
        'material_id' => $fabric->id,
        'shift_id' => $shift->id,
        'initial_quantity' => 100,
    ]);
    // Рулон аксессуаров намеренно НЕ создаём — закройщик их не пришивает.

    auth()->login($cutter);

    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'hasMaterialsInWorkshop');

    expect($method->invoke(null, $orderItem))->toBeTrue();
});

test('hasMaterialsInWorkshop проверяет аксессуары для швеи: нехватка тесьмы ловится', function () {
    $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
    $seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);

    $shift = Shift::factory()->create();
    $seamstress->shifts()->attach($shift->id, ['effective_from' => now()->subDay()->toDateString()]);

    // Швея без кроя: ткань ей не нужна (пропускается), а аксессуары — нужны.
    $fabric = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $accessory = Material::factory()->create(['type_id' => Material::TYPE_ACCESSORY]);
    $item = MarketplaceItem::factory()->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($fabric)->withQuantity(2)->create();
    MaterialConsumption::factory()->forItem($item)->forMaterial($accessory)->withQuantity(1)->create();

    $orderItem = MarketplaceOrderItem::factory()->create([
        'marketplace_item_id' => $item->id,
        'quantity' => 3,
    ]);

    // Рулонов ни ткани, ни аксессуаров в цехе нет.
    auth()->login($seamstress);

    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'hasMaterialsInWorkshop');

    expect($method->invoke(null, $orderItem))->toBeFalse();
});

test('getFilteredItems фильтрует заказы по цеховой настройке orders_filter (FBO/FBS/Все)', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id, 'orders_priority' => 'all']);

    $item = MarketplaceItem::factory()->create();

    $orderFbo = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBO']);
    $orderFbs = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBS']);

    // Заказы со статусом 0 (новый) — берутся закройщиком.
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFbo->id,
        'marketplace_item_id' => $item->id,
        'status' => 0,
        'workshop_id' => null,
        'seamstress_id' => 0,
        'cutter_id' => null,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFbs->id,
        'marketplace_item_id' => $item->id,
        'status' => 0,
        'workshop_id' => null,
        'seamstress_id' => 0,
        'cutter_id' => null,
    ]);

    auth()->login($cutter);
    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'getFilteredItems');

    $filter = fn (string $value) => Setting::query()
        ->where('name', 'orders_filter')
        ->whereNull('workshop_id')
        ->update(['value' => $value]);

    // orders_filter = fbo → FBO попадает, FBS нет.
    $filter('fbo');
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect($ids)->toContain($orderFbo->id);
    expect($ids)->not->toContain($orderFbs->id);

    // orders_filter = fbs → FBS попадает, FBO нет.
    $filter('fbs');
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect($ids)->toContain($orderFbs->id);
    expect($ids)->not->toContain($orderFbo->id);

    // orders_filter = all → оба попадают (без фильтра).
    $filter('all');
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect($ids)->toContain($orderFbo->id);
    expect($ids)->toContain($orderFbs->id);

    auth()->logout();
});

test('getFilteredItems пересечение цеховой orders_filter и персональной user->orders_priority (AND)', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    // Персональная настройка швеи/закройщика жёстко требует FBO.
    $cutter = User::factory()->create(['role_id' => $cutterRole->id, 'orders_priority' => 'fbo']);

    $item = MarketplaceItem::factory()->create();
    $orderFbo = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBO']);
    $orderFbs = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBS']);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFbo->id,
        'marketplace_item_id' => $item->id,
        'status' => 0,
        'workshop_id' => null,
        'seamstress_id' => 0,
        'cutter_id' => null,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFbs->id,
        'marketplace_item_id' => $item->id,
        'status' => 0,
        'workshop_id' => null,
        'seamstress_id' => 0,
        'cutter_id' => null,
    ]);

    auth()->login($cutter);
    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'getFilteredItems');

    $filter = fn (string $value) => Setting::query()
        ->where('name', 'orders_filter')
        ->whereNull('workshop_id')
        ->update(['value' => $value]);

    // Цех=fbs + персональная=fbo → пересечение пусто (ни FBO, ни FBS не проходят).
    $filter('fbs');
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect($ids)->not->toContain($orderFbo->id);
    expect($ids)->not->toContain($orderFbs->id);

    // Цех=fbo + персональная=fbo → проходит только FBO.
    $filter('fbo');
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect($ids)->toContain($orderFbo->id);
    expect($ids)->not->toContain($orderFbs->id);

    auth()->logout();
});

test('getFilteredItems ставит заказ приоритетного FBO-кластера первым (orders_cluster_priority)', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id, 'orders_priority' => 'all']);

    $item = MarketplaceItem::factory()->create();

    // Оба заказа — FBO, одинаковый маркетплейс; отличается только кластер.
    $orderPriority = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1, 'fulfillment_type' => 'FBO', 'cluster' => 'Казань',
        'created_at' => now(),
    ]);
    $orderOther = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1, 'fulfillment_type' => 'FBO', 'cluster' => 'Москва',
        'created_at' => now()->subDay(), // раньше по дате, но приоритет должен победить
    ]);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderPriority->id,
        'marketplace_item_id' => $item->id,
        'status' => 0, 'workshop_id' => null, 'seamstress_id' => 0, 'cutter_id' => null,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderOther->id,
        'marketplace_item_id' => $item->id,
        'status' => 0, 'workshop_id' => null, 'seamstress_id' => 0, 'cutter_id' => null,
    ]);

    auth()->login($cutter);
    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'getFilteredItems');

    // value = "<marketplace_id>|<cluster>" → приоритет "1|Казань".
    Setting::query()->where('name', 'orders_cluster_priority')->whereNull('workshop_id')->update(['value' => '1|Казань']);

    // Казань (CASE=0) должна идти раньше Москвы (CASE=1), несмотря на более позднюю дату.
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect(array_search($orderPriority->id, $ids))->toBeLessThan(array_search($orderOther->id, $ids));

    auth()->logout();
});

test('getFilteredItems без orders_cluster_priority сортирует по дате (regression)', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id, 'orders_priority' => 'all']);

    $item = MarketplaceItem::factory()->create();

    // Более ранний заказ — "Москва", более поздний — "Казань".
    $orderEarlier = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1, 'fulfillment_type' => 'FBO', 'cluster' => 'Москва',
        'created_at' => now()->subDays(2),
    ]);
    $orderLater = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1, 'fulfillment_type' => 'FBO', 'cluster' => 'Казань',
        'created_at' => now()->subDay(),
    ]);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderEarlier->id,
        'marketplace_item_id' => $item->id,
        'status' => 0, 'workshop_id' => null, 'seamstress_id' => 0, 'cutter_id' => null,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderLater->id,
        'marketplace_item_id' => $item->id,
        'status' => 0, 'workshop_id' => null, 'seamstress_id' => 0, 'cutter_id' => null,
    ]);

    auth()->login($cutter);
    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'getFilteredItems');

    // Приоритет выключен → порядок по created_at (ранняя "Москва" раньше "Казани").
    Setting::query()->where('name', 'orders_cluster_priority')->whereNull('workshop_id')->update(['value' => '']);

    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect(array_search($orderEarlier->id, $ids))->toBeLessThan(array_search($orderLater->id, $ids));

    auth()->logout();
});

test('getFilteredItems: FBS без кластера идёт после приоритетного FBO-кластера', function () {
    $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $cutterRole->id, 'orders_priority' => 'all']);

    $item = MarketplaceItem::factory()->create();

    $orderFboKazan = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1, 'fulfillment_type' => 'FBO', 'cluster' => 'Казань',
        'created_at' => now(),
    ]);
    $orderFbs = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1, 'fulfillment_type' => 'FBS', 'cluster' => null,
        'created_at' => now()->subDay(), // раньше по дате, но приоритет/fulfillment должны победить
    ]);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFboKazan->id,
        'marketplace_item_id' => $item->id,
        'status' => 0, 'workshop_id' => null, 'seamstress_id' => 0, 'cutter_id' => null,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFbs->id,
        'marketplace_item_id' => $item->id,
        'status' => 0, 'workshop_id' => null, 'seamstress_id' => 0, 'cutter_id' => null,
    ]);

    auth()->login($cutter);
    $method = new ReflectionMethod(MarketplaceOrderItemService::class, 'getFilteredItems');

    Setting::query()->where('name', 'orders_cluster_priority')->whereNull('workshop_id')->update(['value' => '1|Казань']);

    // FBO "Казань" — приоритетный (CASE=0), FBS без кластера — после (CASE=1).
    $ids = $method->invoke(null)->pluck('marketplace_order_id')->toArray();
    expect(array_search($orderFboKazan->id, $ids))->toBeLessThan(array_search($orderFbs->id, $ids));

    auth()->logout();
});

test('clustersByMarketplace: OZON — по полю cluster (город), WB — по полю name (склад)', function () {
    // OZON: несколько складов мапятся на один кластер-город.
    MarketplaceWarehouse::create(['name' => 'АПТЕКА_НИНО', 'marketplace_id' => 1, 'cluster' => 'Казань']);
    MarketplaceWarehouse::create(['name' => 'ВЕТАПТЕКА_НИНО', 'marketplace_id' => 1, 'cluster' => 'Казань']);
    MarketplaceWarehouse::create(['name' => 'АДЫГЕЙСК_РФЦ', 'marketplace_id' => 1, 'cluster' => 'Краснодар']);

    // WB: cluster пустой — кластером служит name.
    MarketplaceWarehouse::create(['name' => 'Казань', 'marketplace_id' => 2, 'cluster' => '']);
    MarketplaceWarehouse::create(['name' => 'Воронеж', 'marketplace_id' => 2, 'cluster' => '']);

    // OZON: distinct по cluster → 2 города (Казань не дублируется).
    $ozon = MarketplaceWarehouse::clustersByMarketplace(1);
    expect(array_values($ozon))->toBe(['Казань', 'Краснодар']);
    expect($ozon)->not->toHaveKey('АПТЕКА_НИНО'); // не name

    // WB: по name.
    $wb = MarketplaceWarehouse::clustersByMarketplace(2);
    expect(array_values($wb))->toBe(['Воронеж', 'Казань']);
});

test('clusterOptions включает OZON (по cluster) и WB (по name) с префиксом маркетплейса', function () {
    MarketplaceWarehouse::create(['name' => 'АПТЕКА_НИНО', 'marketplace_id' => 1, 'cluster' => 'Казань']);
    MarketplaceWarehouse::create(['name' => 'Казань', 'marketplace_id' => 2, 'cluster' => '']);

    $options = MarketplaceWarehouse::clusterOptions();

    expect($options)->toHaveKey('1|Казань');
    expect($options['1|Казань'])->toBe('OZON — Казань');
    expect($options)->toHaveKey('2|Казань');
    expect($options['2|Казань'])->toBe('WB — Казань');
});

test('resetClusterPriorityIfExhausted сбрасывает цеховую настройку когда все заказы кластера в стикеровке', function () {
    // Создаём настройки: цеховая (workshop_id=1) и глобальная
    Setting::query()->where('name', 'orders_cluster_priority')->whereNull('workshop_id')->update(['value' => '1|Казань']);
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 1], ['value' => '1|Казань']);
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 2], ['value' => '1|Воронеж']);

    // Создаём заказы: кластер Казань в цехе 1 и кластер Воронеж в цехе 2
    $marketplaceKazan = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Казань',
    ]);
    $itemKazan = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceKazan->id,
        'status' => 5, // в стикеровке
        'workshop_id' => 1,
    ]);

    $marketplaceVoronezh = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Воронеж',
    ]);
    $itemVoronezh = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplaceVoronezh->id,
        'status' => 5, // в стикеровке
        'workshop_id' => 2,
    ]);

    // Вызываем для цеха 1 — все заказы Казань в стикеровке, значит должно сброситься
    MarketplaceOrderItemService::resetClusterPriorityIfExhausted(1);

    // Проверяем: цеховая настройка для цеха 1 сброшена, цех 2 и глобальная нет
    expect(Setting::getValue('orders_cluster_priority', 1))->toBe('');
    expect(Setting::getValue('orders_cluster_priority', 2))->toBe('1|Воронеж');
    expect(Setting::getValue('orders_cluster_priority'))->toBe('1|Казань');
});

test('resetClusterPriorityIfExhausted НЕ сбрасывает если остались заказы кластера в очереди', function () {
    // Создаём настройку для цеха 1
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 1], ['value' => '1|Казань']);

    // Создаём заказы: один в стикеровке, один в работе
    $marketplace = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Казань',
    ]);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplace->id,
        'status' => 5, // в стикеровке
        'workshop_id' => 1,
    ]);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplace->id,
        'status' => 4, // в работе
        'workshop_id' => 1,
    ]);

    // Вызываем — не должно сброситься, т.к. есть заказ в работе
    MarketplaceOrderItemService::resetClusterPriorityIfExhausted(1);

    expect(Setting::getValue('orders_cluster_priority', 1))->toBe('1|Казань');
});

test('resetClusterPriorityIfExhausted НЕ сбрасывает преждевременно если есть новый заказ кластера без цеха', function () {
    // Создаём настройку для цеха 1
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 1], ['value' => '1|Казань']);

    $marketplace = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Казань',
    ]);

    // Заказ в цехе 1 в стикеровке
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplace->id,
        'status' => 5,
        'workshop_id' => 1,
    ]);

    // Новый заказ того же кластера без цеха (workshop_id = NULL)
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplace->id,
        'status' => 0, // новый
        'workshop_id' => null,
    ]);

    // Вызываем — не должно сброситься, т.к. есть новый заказ кластера
    MarketplaceOrderItemService::resetClusterPriorityIfExhausted(1);

    expect(Setting::getValue('orders_cluster_priority', 1))->toBe('1|Казань');
});

test('resetClusterPriorityIfExhausted выходит молча если настройка пуста', function () {
    // Создаём пустую настройку
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 1], ['value' => '']);

    // Вызываем — не должно упасть
    MarketplaceOrderItemService::resetClusterPriorityIfExhausted(1);

    expect(Setting::getValue('orders_cluster_priority', 1))->toBe('');
});

test('resetClusterPriorityIfExhausted учитывает все статусы очереди (0,4,7,8)', function () {
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 1], ['value' => '1|Казань']);

    $marketplace = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Казань',
    ]);

    // Создаём заказы во всех статусах очереди кроме 5 (стикеровка)
    foreach ([0, 4, 7, 8] as $status) {
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $marketplace->id,
            'status' => $status,
            'workshop_id' => 1,
        ]);
    }

    // Вызываем — не должно сброситься
    MarketplaceOrderItemService::resetClusterPriorityIfExhausted(1);

    expect(Setting::getValue('orders_cluster_priority', 1))->toBe('1|Казань');
});

test('resetClusterPriorityIfExhausted при workshop_id=null сбрасывает только истощённые цехи', function () {
    // Создаём настройки: две цеховые (workshop_id=1 и workshop_id=2) и глобальная
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 1], ['value' => '1|Казань']);
    Setting::query()->firstOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => 2], ['value' => '1|Казань']);
    Setting::query()->updateOrCreate(['name' => 'orders_cluster_priority', 'workshop_id' => null], ['value' => '1|Казань']);

    // Создаём заказ кластера Казань
    $marketplace = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Казань',
    ]);

    // Создаём item для цеха 1 со статусом 13 (уже в стикеровке — не входит в очередь [0,4,7,8])
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplace->id,
        'status' => 13,
        'workshop_id' => 1,
    ]);

    // Создаём отдельный заказ того же кластера для цеха 2
    $marketplace2 = MarketplaceOrder::factory()->create([
        'marketplace_id' => 1,
        'cluster' => 'Казань',
    ]);

    // Создаём item для цеха 2 со статусом 0 (в очереди)
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $marketplace2->id,
        'status' => 0,
        'workshop_id' => 2,
    ]);

    // Вызываем с workshop_id=null — должен сбросить только истощённый цех 1
    MarketplaceOrderItemService::resetClusterPriorityIfExhausted(null);

    // Проверяем: цех 1 сброшен, цех 2 не изменён, глобальная не тронута
    expect(Setting::getValue('orders_cluster_priority', 1))->toBe('');
    expect(Setting::getValue('orders_cluster_priority', 2))->toBe('1|Казань');
    expect(Setting::getValue('orders_cluster_priority'))->toBe('1|Казань');
});

test('stickedByCluster группирует товары на стикеровке (статус 5) по кластеру заказа', function () {
    $moscow = MarketplaceOrder::factory()->create(['cluster' => 'Москва']);
    $tula = MarketplaceOrder::factory()->create(['cluster' => 'Тула']);

    // 2 товара в Москве + 1 в Туле, все на стикеровке
    MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $moscow->id, 'status' => 5]);
    MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $moscow->id, 'status' => 5]);
    MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $tula->id, 'status' => 5]);

    $result = MarketplaceOrderItemService::stickedByCluster();

    expect($result)->toBe(['Москва' => 2, 'Тула' => 1]);
});

test('stickedByCluster игнорирует товары не в статусе 5', function () {
    $moscow = MarketplaceOrder::factory()->create(['cluster' => 'Москва']);

    foreach ([0, 4, 7, 8] as $status) {
        MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $moscow->id, 'status' => $status]);
    }

    $result = MarketplaceOrderItemService::stickedByCluster();

    expect($result)->toBe([]);
});

test('stickedByCluster игнорирует товары с пустым кластером заказа', function () {
    $noCluster = MarketplaceOrder::factory()->create(['cluster' => null]);

    MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $noCluster->id, 'status' => 5]);

    $result = MarketplaceOrderItemService::stickedByCluster();

    expect($result)->toBe([]);
});

test('stickedByCluster фильтрует по workshop_id', function () {
    $moscow = MarketplaceOrder::factory()->create(['cluster' => 'Москва']);

    MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $moscow->id, 'status' => 5, 'workshop_id' => 1]);
    MarketplaceOrderItem::factory()->create(['marketplace_order_id' => $moscow->id, 'status' => 5, 'workshop_id' => 2]);

    $result = MarketplaceOrderItemService::stickedByCluster(1);

    expect($result)->toBe(['Москва' => 1]);
});
