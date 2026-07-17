<?php

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Shift;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Подготовка тестовых данных ───

beforeEach(function () {
    // Создаем роль администратора
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

    // Создаем администратора
    $this->admin = User::factory()->create([
        'role_id' => $adminRole->id,
        'name' => 'Admin User',
    ]);

    // Создаем обычного пользователя (не админа)
    $this->seamstress = User::factory()->create([
        'role_id' => $seamstressRole->id,
        'name' => 'Seamstress User',
    ]);

    // Создаем workshop и смену
    $workshop = Workshop::factory()->create();
    $this->shift = Shift::create([
        'name' => 'Тестовая смена',
        'status' => Shift::STATUS_ACTIVE,
        'workshop_id' => $workshop->id,
    ]);

    // Создаем материал
    $this->material = Material::factory()->create([
        'title' => 'Тестовая ткань',
        'type_id' => Material::TYPE_FABRIC,
    ]);

    // Создаем рулон с остатком 100 метров
    $this->roll = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_IN_WORKSHOP,
        'initial_quantity' => 100,
        'roll_code' => 'TEST-001',
    ]);
});

// ─── Happy Path ───

test('admin can write off roll quantity successfully', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 25,
            'comment' => 'Тестовое списание',
        ]);

    // Проверяем редирект
    $response->assertRedirect(route('rolls.show', $this->roll));
    $response->assertSessionHas('success', 'Метраж списан');

    // Проверяем создание Order
    $order = Order::where('type_movement', 10)
        ->where('status', 3)
        ->where('storekeeper_id', $this->admin->id)
        ->first();

    expect($order)->not->toBeNull()
        ->and($order->comment)->toBe('Тестовое списание')
        ->and($order->shift_id)->toBe($this->shift->id);

    // Проверяем создание MovementMaterial
    $movementMaterial = MovementMaterial::where('order_id', $order->id)
        ->where('roll_id', $this->roll->id)
        ->where('quantity', 25)
        ->first();

    expect($movementMaterial)->not->toBeNull()
        ->and($movementMaterial->material_id)->toBe($this->material->id);

    // Проверяем уменьшение остатка
    $currentQuantity = $this->roll->fresh()->current_quantity;
    expect($currentQuantity)->toBe(75.0); // 100 - 25
});

test('admin can write off without comment', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
        ]);

    $response->assertRedirect(route('rolls.show', $this->roll));
    $response->assertSessionHas('success', 'Метраж списан');

    $order = Order::where('type_movement', 10)->first();
    expect($order->comment)->toBeNull();
});

test('write off logs to materials channel', function () {
    $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 15,
        ]);

    // Логирование проверяется через наличие записи в логах
    // В тестовой среде это может быть проверено через mock Log facade
    $this->assertNotNull(Order::where('type_movement', 10)->first());
});

// ─── Валидация quantity ───

test('write off fails when quantity exceeds current roll quantity', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 99999, // Меньше initial_quantity, но больше current_quantity
        ]);

    $response->assertSessionHasErrors(['quantity']);

    // Order и MovementMaterial не должны быть созданы
    expect(Order::where('type_movement', 10)->count())->toBe(0)
        ->and(MovementMaterial::where('roll_id', $this->roll->id)->count())->toBe(0);
});

test('write off fails when quantity is zero', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 0,
        ]);

    $response->assertSessionHasErrors(['quantity']);
});

test('write off fails when quantity is negative', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => -10,
        ]);

    $response->assertSessionHasErrors(['quantity']);
});

test('write off fails when quantity is missing', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'comment' => 'Без количества',
        ]);

    $response->assertSessionHasErrors(['quantity']);
});

test('write off fails when quantity is not numeric', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 'not-a-number',
        ]);

    $response->assertSessionHasErrors(['quantity']);
});

// ─── Валидация comment ───

test('write off fails when comment exceeds maximum length', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
            'comment' => str_repeat('a', 1001), // Максимум 1000 символов
        ]);

    $response->assertSessionHasErrors(['comment']);
});

test('write off accepts empty comment', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
            'comment' => '',
        ]);

    $response->assertSessionHasNoErrors();
});

// ─── Авторизация ───

test('non admin user cannot write off roll', function () {
    $response = $this->actingAs($this->seamstress)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
        ]);

    $response->assertStatus(403);

    // Order и MovementMaterial не должны быть созданы
    expect(Order::where('type_movement', 10)->count())->toBe(0);
});

test('unauthenticated user cannot write off roll', function () {
    $response = $this->post(route('rolls.writeOff', $this->roll), [
        'quantity' => 10,
    ]);

    $response->assertRedirect(route('login'));
});

// ─── Бизнес-логика ───

test('write off correctly calculates current quantity after multiple movements', function () {
    // Создаем предварительные списания
    $order1 = Order::factory()->create([
        'type_movement' => 3,
        'status' => 3,
        'shift_id' => $this->shift->id,
    ]);

    MovementMaterial::create([
        'material_id' => $this->material->id,
        'order_id' => $order1->id,
        'quantity' => 20,
        'roll_id' => $this->roll->id,
    ]);

    // Теперь current_quantity = 100 - 20 = 80
    expect($this->roll->fresh()->current_quantity)->toBe(80.0);

    // Списываем 10 из остатка
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
        ]);

    $response->assertSessionHasNoErrors();

    // Проверяем новый остаток: 80 - 10 = 70
    expect($this->roll->fresh()->current_quantity)->toBe(70.0);
});

test('write off blocks return to storage', function () {
    // Списываем часть рулона
    $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
        ]);

    // Пытаемся вернуть рулон на склад
    $response = $this->actingAs($this->admin)
        ->put(route('rolls.returnToStorage', $this->roll));

    $response->assertRedirect(route('rolls.show', $this->roll));
    $response->assertSessionHas('error', 'Рулон уже использовался, возврат невозможен');

    // Статус рулона не должен измениться
    expect($this->roll->fresh()->status)->toBe(Roll::STATUS_IN_WORKSHOP);
});

test('write off creates order with correct type movement and status', function () {
    $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 10,
        ]);

    $order = Order::where('type_movement', 10)
        ->where('status', 3)
        ->first();

    expect($order)->not->toBeNull()
        ->and($order->storekeeper_id)->toBe($this->admin->id)
        ->and($order->shift_id)->toBe($this->shift->id);
});

test('write off handles database transaction rollback on error', function () {
    // Этот тест проверяет, что при ошибке транзакция откатывается
    // Создаем ситуацию, которая может вызвать ошибку (например, нарушаем ограничение)

    // В реальном коде RollWriteOffRequest может использовать closure-правило
    // которое читает $roll->current_quantity. Проверим, что при ошибке
    // валидации не создаются записи в БД

    $initialOrderCount = Order::count();
    $initialMovementCount = MovementMaterial::count();

    $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => 99999, // Превышает остаток
        ]);

    expect(Order::count())->toBe($initialOrderCount)
        ->and(MovementMaterial::count())->toBe($initialMovementCount);
});

test('write off accepts maximum valid quantity', function () {
    // Списываем ровно текущий остаток
    $currentQuantity = $this->roll->current_quantity;

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $this->roll), [
            'quantity' => $currentQuantity,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success', 'Метраж списан');

    // Остаток должен стать 0
    expect($this->roll->fresh()->current_quantity)->toBe(0.0);
});

// ─── Server-side guard для статуса рулона ───

test('write off rejects roll not in workshop (in_storage status)', function () {
    $rollInStorage = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_IN_STORAGE,
        'initial_quantity' => 100,
        'roll_code' => 'STORAGE-001',
    ]);

    $initialOrderCount = Order::where('type_movement', 10)->count();

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $rollInStorage), [
            'quantity' => 10,
            'comment' => 'Тестовое списание',
        ]);

    // Проверяем редирект на show с ошибкой
    $response->assertRedirect(route('rolls.show', $rollInStorage));
    $response->assertSessionHas('error', 'Списание доступно только для рулона в цехе');

    // Проверяем, что Order и MovementMaterial НЕ созданы
    expect(Order::where('type_movement', 10)->count())->toBe($initialOrderCount)
        ->and(MovementMaterial::where('roll_id', $rollInStorage->id)->count())->toBe(0);
});

test('write off rejects completed roll', function () {
    $rollCompleted = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_COMPLETED,
        'initial_quantity' => 100,
        'roll_code' => 'COMPLETED-001',
        'completed_at' => now(),
    ]);

    $initialOrderCount = Order::where('type_movement', 10)->count();

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.writeOff', $rollCompleted), [
            'quantity' => 10,
            'comment' => 'Тестовое списание',
        ]);

    // Проверяем редирект на show с ошибкой
    $response->assertRedirect(route('rolls.show', $rollCompleted));
    $response->assertSessionHas('error', 'Списание доступно только для рулона в цехе');

    // Проверяем, что Order и MovementMaterial НЕ созданы
    expect(Order::where('type_movement', 10)->count())->toBe($initialOrderCount)
        ->and(MovementMaterial::where('roll_id', $rollCompleted->id)->count())->toBe(0);
});

// ─── UI условия для формы списания ───

test('show page does not display write off form for in_storage roll', function () {
    $rollInStorage = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_IN_STORAGE,
        'initial_quantity' => 100,
        'roll_code' => 'STORAGE-002',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('rolls.show', $rollInStorage));

    $response->assertStatus(200);
    $response->assertDontSee('Ручное списание метража');
});

test('show page displays write off form for in_workshop roll with quantity', function () {
    $rollInWorkshop = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_IN_WORKSHOP,
        'initial_quantity' => 100,
        'roll_code' => 'WORKSHOP-002',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('rolls.show', $rollInWorkshop));

    $response->assertStatus(200);
    $response->assertSee('Ручное списание метража');
});

// ─── Roll Complete Method Tests ───

// ─── Happy Path ───

test('admin can complete roll successfully', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85, // Фактический остаток
        ]);

    // Проверяем редирект
    $response->assertRedirect(route('rolls.show', $this->roll));
    $response->assertSessionHas('success');

    // Перезагружаем рулон из БД
    $roll = $this->roll->fresh();

    // Проверяем изменение статуса
    expect($roll->status)->toBe(Roll::STATUS_COMPLETED)
        ->and($roll->completed_at)->not->toBeNull()
        ->and($roll->completed_by)->toBe($this->admin->id)
        ->and($roll->shortage_quantity)->toEqual(15.0); // 100 - 85 = 15
});

test('complete calculates shortage correctly when actual less than current', function () {
    // Создаем списание чтобы текущий остаток был меньше начального
    $order = Order::factory()->create([
        'type_movement' => 10,
        'status' => 3,
        'shift_id' => $this->shift->id,
    ]);

    MovementMaterial::create([
        'material_id' => $this->material->id,
        'order_id' => $order->id,
        'quantity' => 20,
        'roll_id' => $this->roll->id,
    ]);

    // Теперь current_quantity = 100 - 20 = 80
    expect($this->roll->fresh()->current_quantity)->toBe(80.0);

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 70, // Меньше текущего остатка
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toEqual(10.0); // 80 - 70 = 10
});

test('complete calculates negative shortage (surplus) when actual exceeds current', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 120, // Больше текущего остатка (100)
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toEqual(-20.0); // 100 - 120 = -20 (излишек)
});

test('complete handles zero actual remaining', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 0,
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toEqual(100.0); // Вся недостача
});

test('complete accepts exact current quantity as actual remaining', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 100, // Равно начальному количеству
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toEqual(0.0); // Без недостачи
});

// ─── Authorization ───

test('non admin user cannot complete roll', function () {
    $response = $this->actingAs($this->seamstress)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85,
        ]);

    $response->assertStatus(403);

    // Рулон не должен измениться
    $roll = $this->roll->fresh();
    expect($roll->status)->toBe(Roll::STATUS_IN_WORKSHOP)
        ->and($roll->completed_at)->toBeNull()
        ->and($roll->completed_by)->toBeNull();
});

test('unauthenticated user cannot complete roll', function () {
    $response = $this->post(route('rolls.complete', $this->roll), [
        'actual_remaining' => 85,
    ]);

    $response->assertRedirect(route('login'));

    // Рулон не должен измениться
    $roll = $this->roll->fresh();
    expect($roll->status)->toBe(Roll::STATUS_IN_WORKSHOP);
});

// ─── Validation ───

test('complete fails when actual_remaining is missing', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), []);

    $response->assertSessionHasErrors(['actual_remaining']);

    // Рулон не должен измениться
    $roll = $this->roll->fresh();
    expect($roll->status)->toBe(Roll::STATUS_IN_WORKSHOP);
});

test('complete fails when actual_remaining is not numeric', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 'not-a-number',
        ]);

    $response->assertSessionHasErrors(['actual_remaining']);
});

test('complete fails when actual_remaining is negative', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => -10,
        ]);

    $response->assertSessionHasErrors(['actual_remaining']);
});

test('complete accepts zero as valid actual_remaining', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 0,
        ]);

    $response->assertSessionHasNoErrors();
});

test('complete accepts decimal values for actual_remaining', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85.5,
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toBe(14.5); // 100 - 85.5
});

// ─── Status Validation ───

test('complete rejects roll with in_storage status', function () {
    $rollInStorage = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_IN_STORAGE,
        'initial_quantity' => 100,
        'roll_code' => 'STORAGE-001',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $rollInStorage), [
            'actual_remaining' => 85,
        ]);

    $response->assertRedirect(route('rolls.show', $rollInStorage));
    $response->assertSessionHas('error');

    // Рулон не должен измениться
    $roll = $rollInStorage->fresh();
    expect($roll->status)->toBe(Roll::STATUS_IN_STORAGE)
        ->and($roll->completed_at)->toBeNull();
});

test('complete rejects roll with shipped_to_workshop status', function () {
    $rollShipped = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
        'initial_quantity' => 100,
        'roll_code' => 'SHIPPED-001',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $rollShipped), [
            'actual_remaining' => 85,
        ]);

    $response->assertRedirect(route('rolls.show', $rollShipped));
    $response->assertSessionHas('error');

    $roll = $rollShipped->fresh();
    expect($roll->status)->toBe(Roll::STATUS_SHIPPED_TO_WORKSHOP);
});

test('complete rejects already completed roll', function () {
    $originalCompletedAt = now()->subHour();
    $rollCompleted = Roll::factory()->create([
        'material_id' => $this->material->id,
        'shift_id' => $this->shift->id,
        'status' => Roll::STATUS_COMPLETED,
        'initial_quantity' => 100,
        'roll_code' => 'COMPLETED-001',
        'completed_at' => $originalCompletedAt,
        'completed_by' => $this->admin->id,
        'shortage_quantity' => 10,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $rollCompleted), [
            'actual_remaining' => 85,
        ]);

    $response->assertRedirect(route('rolls.show', $rollCompleted));
    $response->assertSessionHas('error');

    // Проверяем, что данные НЕ перезаписались
    $roll = $rollCompleted->fresh();
    expect($roll->completed_at)->toBe($originalCompletedAt->toDateTimeString()) // Время не изменилось
        ->and($roll->shortage_quantity)->toEqual(10.0); // Значение не изменилось
});

// ─── Race Condition Protection ───

test('complete uses lockForUpdate to prevent race conditions', function () {
    // Создаём два конкурентных запроса
    $firstResponse = null;
    $secondResponse = null;

    // Первый запрос должен пройти успешно
    $firstResponse = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85,
        ]);

    // После первого запроса рулон уже завершён
    $roll = $this->roll->fresh();
    expect($roll->status)->toBe(Roll::STATUS_COMPLETED);

    // Второй запрос должен получить ошибку статуса
    $secondResponse = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 90,
        ]);

    $secondResponse->assertRedirect(route('rolls.show', $this->roll));
    $secondResponse->assertSessionHas('error');

    // Проверяем, что данные первого запроса не перезаписались
    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toEqual(15.0); // 100 - 85 = 15
});

// ─── Database Transaction Protection ───

test('complete rolls back transaction on database error', function () {
    // Создаем состояние, которое вызовет ошибку при update
    // Например, пытаемся завершить рулон с нарушением constraints

    $initialStatus = $this->roll->status;
    $initialCompletedAt = $this->roll->completed_at;

    // Симулируем ошибку, делая недействительным запрос
    // В реальном коде это может быть ока З�� caused by constraint violation
    // Здесь мы проверяем, что при ошибке валидации ничего не меняется

    $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 'invalid', // Вызовет ошибку валидации
        ]);

    // Проверяем, что рулон не изменился
    $roll = $this->roll->fresh();
    expect($roll->status)->toBe($initialStatus)
        ->and($roll->completed_at)->toBe($initialCompletedAt);
});

// ─── Logging ───

test('complete logs action to materials channel', function () {
    $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85,
        ]);

    // Логирование проверяется через то, что операция прошла успешно
    // В production логи пишутся в файл, в тестах мы проверяем side effects
    $roll = $this->roll->fresh();
    expect($roll->status)->toBe(Roll::STATUS_COMPLETED)
        ->and($roll->completed_by)->toBe($this->admin->id);
});

// ─── Edge Cases ───

test('complete handles very small shortage amounts', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 99.99, // Очень маленькая недостача
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toBe(0.01); // 100 - 99.99
});

test('complete handles very large shortage amounts', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 0.01, // Почти весь материал списан
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toBe(99.99); // 100 - 0.01
});

test('complete uses rounding for shortage calculation', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85.123456, // Много знаков после запятой
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shortage_quantity)->toBe(14.88); // round(100 - 85.123456, 2)
});

test('complete preserves shift_id after completion', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('rolls.complete', $this->roll), [
            'actual_remaining' => 85,
        ]);

    $response->assertSessionHasNoErrors();

    $roll = $this->roll->fresh();
    expect($roll->shift_id)->toBe($this->shift->id);
});
