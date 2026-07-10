<?php

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Models\Workshop;
use App\Services\RatingBoard\RatingBoardDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Настройка глобального токена для тестов
    config(['services.rating_board.token' => 'test-token-123']);
});

/**
 * Тест метода getLeaders для швей (role_id=1).
 * Проверяет подсчёт выполненных заказов за сегодня по колонке completed_at.
 */
test('getLeaders counts completed orders for seamstress today', function () {
    // Arrange: создаём цех и двух швей
    $workshop = Workshop::factory()->create();
    $seamstressA = User::factory()->create(['role_id' => 1, 'name' => 'Швея А']);
    $seamstressB = User::factory()->create(['role_id' => 1, 'name' => 'Швея Б']);

    // Создаём заказы: у швеи A - 3 заказа сегодня, у швеи B - 1 заказ сегодня
    $today = Carbon::today()->toDateString().' 12:00:00';
    MarketplaceOrderItem::factory()->count(3)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressA->id,
        'completed_at' => $today,
        'status' => 3, // completed
    ]);

    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressB->id,
        'completed_at' => $today,
        'status' => 3,
    ]);

    // Act: получаем лидеров
    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    // Assert: проверяем, что швея A на 1-м месте с gold, швея B на 2-м с silver
    expect($leaders)->toHaveCount(2);
    expect($leaders[0])->toMatchArray([
        'id' => $seamstressA->id,
        'name' => 'Швея А',
        'position' => 1,
        'medal' => 'gold',
        'count' => 3,
    ]);
    expect($leaders[1])->toMatchArray([
        'id' => $seamstressB->id,
        'name' => 'Швея Б',
        'position' => 2,
        'medal' => 'silver',
        'count' => 1,
    ]);
});

/**
 * Тест метода getLeaders для закройщиков (role_id=4).
 * Проверяет подсчёт по колонке cutting_completed_at.
 */
test('getLeaders counts cutting_completed_orders for cutter', function () {
    $workshop = Workshop::factory()->create();
    $cutterA = User::factory()->create(['role_id' => 4, 'name' => 'Закройщик А']);
    $cutterB = User::factory()->create(['role_id' => 4, 'name' => 'Закройщик Б']);

    $today = Carbon::today()->toDateString().' 12:00:00';
    MarketplaceOrderItem::factory()->count(2)->create([
        'workshop_id' => $workshop->id,
        'cutter_id' => $cutterA->id,
        'cutting_completed_at' => $today,
        'status' => 3,
    ]);

    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'cutter_id' => $cutterB->id,
        'cutting_completed_at' => $today,
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    expect($leaders)->toHaveCount(2);
    expect($leaders[0]['count'])->toBe(2);
    expect($leaders[1]['count'])->toBe(1);
});

/**
 * Тест метода getLeaders для ОТК (role_id=5).
 * Проверяет подсчёт по колонке packed_at.
 */
test('getLeaders counts packed_orders for otk', function () {
    $workshop = Workshop::factory()->create();
    $otkA = User::factory()->create(['role_id' => 5, 'name' => 'ОТК А']);
    $otkB = User::factory()->create(['role_id' => 5, 'name' => 'ОТК Б']);

    $today = Carbon::today()->toDateString().' 12:00:00';
    MarketplaceOrderItem::factory()->count(4)->create([
        'workshop_id' => $workshop->id,
        'otk_id' => $otkA->id,
        'packed_at' => $today,
        'status' => 3,
    ]);

    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'otk_id' => $otkB->id,
        'packed_at' => $today,
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    expect($leaders)->toHaveCount(2);
    expect($leaders[0]['count'])->toBe(4);
    expect($leaders[1]['count'])->toBe(1);
});

/**
 * Тест метода getStickers.
 * Проверяет подсчёт заказов со статусом 5 (стикеровка) по FBO/FBS.
 */
test('getStickers counts items with status 5 by fulfillment_type', function () {
    $workshop = Workshop::factory()->create();

    // Создаём заказы FBO со статусом 5
    $orderFBO = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBO']);
    MarketplaceOrderItem::factory()->count(2)->create([
        'marketplace_order_id' => $orderFBO->id,
        'workshop_id' => $workshop->id,
        'status' => 5, // stickering
    ]);

    // Создаём заказ FBS со статусом 5
    $orderFBS = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBS']);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderFBS->id,
        'workshop_id' => $workshop->id,
        'status' => 5,
    ]);

    // Создаём заказ с другим статусом (не должен учитываться)
    $orderOther = MarketplaceOrder::factory()->create(['fulfillment_type' => 'FBO']);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $orderOther->id,
        'workshop_id' => $workshop->id,
        'status' => 4, // not stickering
    ]);

    $service = new RatingBoardDataService;
    $stickers = $service->getStickers($workshop->id);

    expect($stickers)->toMatchArray([
        'fbo' => 2,
        'fbs' => 1,
    ]);
});

/**
 * Тест метода getStatistics.
 * Проверяет, что учитываются только заказы текущего месяца.
 */
test('getStatistics only counts orders in current month', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1]);

    // Заказ текущего месяца
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::now()->toDateString().' 12:00:00',
        'status' => 3,
    ]);

    // Заказ прошлого месяца
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::now()->subMonth()->toDateString().' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['value'])->toContain('выполнено 1 заказов!');
});

/**
 * Тест метода getWinner.
 * Проверяет определение победителя за предыдущий рабочий день.
 */
test('getWinner returns top employee from previous work day', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Победитель']);

    // Создаём смену и расписание на вчера
    $shift = Shift::factory()->create(['workshop_id' => $workshop->id, 'name' => 'Утренняя']);
    ShiftSchedule::factory()->create([
        'workshop_id' => $workshop->id,
        'shift_id' => $shift->id,
        'date' => Carbon::yesterday()->toDateString(),
    ]);

    // Создаём выполненный заказ на вчера
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::yesterday()->toDateString().' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $winner = $service->getWinner($workshop->id);

    expect($winner)->toMatchArray([
        'name' => 'Победитель',
        'orders_count' => 1,
        'description' => 'Мастерство, покоряющее сердца.',
    ]);
    expect($winner['avatar'])->not->toBeNull();
});

/**
 * Тест метода getWinner без данных.
 * Проверяет возврат null-значений при отсутствии заказов.
 */
test('getWinner returns null when no orders exist', function () {
    $workshop = Workshop::factory()->create();

    $service = new RatingBoardDataService;
    $winner = $service->getWinner($workshop->id);

    expect($winner)->toMatchArray([
        'name' => null,
        'avatar' => null,
        'orders_count' => 0,
        'description' => null,
    ]);
});

/**
 * Тест метода getTimers.
 * Проверяет расчёт секунд до начала/конца рабочего дня.
 */
test('getTimers calculates seconds based on working day settings', function () {
    $workshop = Workshop::factory()->create();

    // Настройки рабочего времени (глобальные)
    Setting::updateOrCreate(
        ['name' => 'working_day_start', 'workshop_id' => null],
        ['value' => '07:00']
    );
    Setting::updateOrCreate(
        ['name' => 'working_day_end', 'workshop_id' => null],
        ['value' => '20:00']
    );

    $service = new RatingBoardDataService;
    $timers = $service->getTimers($workshop->id);

    // Проверяем структуру
    expect($timers)->toHaveKeys(['morning_seconds_left', 'evening_seconds_left']);
    expect($timers['morning_seconds_left'])->toBeInt();
    expect($timers['evening_seconds_left'])->toBeInt();
    expect($timers['morning_seconds_left'])->toBeGreaterThanOrEqual(0);
    expect($timers['evening_seconds_left'])->toBeGreaterThanOrEqual(0);
});

/**
 * Тест метода getPodium.
 * Проверяет формирование подиума (топ-3).
 */
test('getPodium returns top 3 leaders with medals', function () {
    $workshop = Workshop::factory()->create();

    // Создаём 3 лидеров
    $leaders = [
        ['id' => 1, 'name' => 'Первый', 'avatar' => '/avatar1.png', 'count' => 10, 'position' => 1],
        ['id' => 2, 'name' => 'Второй', 'avatar' => '/avatar2.png', 'count' => 7, 'position' => 2],
        ['id' => 3, 'name' => 'Третий', 'avatar' => '/avatar3.png', 'count' => 5, 'position' => 3],
    ];

    $service = new RatingBoardDataService;
    $podium = $service->getPodium($leaders);

    expect($podium)->toMatchArray([
        'gold' => [
            'id' => 1,
            'name' => 'Первый',
            'avatar' => '/avatar1.png',
            'text' => 'Высшая лига портновского искусства.',
        ],
        'silver' => [
            'id' => 2,
            'name' => 'Второй',
            'avatar' => '/avatar2.png',
            'text' => null,
        ],
        'bronze' => [
            'id' => 3,
            'name' => 'Третий',
            'avatar' => '/avatar3.png',
            'text' => null,
        ],
    ]);
});

/**
 * Тест метода getShift.
 * Проверяет получение текущей и предыдущей смены.
 */
test('getShift returns current and previous shift names', function () {
    $workshop = Workshop::factory()->create();

    // Создаём смену на сегодня
    $shift = Shift::factory()->create(['workshop_id' => $workshop->id, 'name' => 'Дневная']);
    ShiftSchedule::factory()->create([
        'workshop_id' => $workshop->id,
        'shift_id' => $shift->id,
        'date' => Carbon::today()->toDateString(),
    ]);

    $service = new RatingBoardDataService;
    $shiftData = $service->getShift($workshop->id);

    expect($shiftData)->toHaveKeys(['name', 'previous_name']);
    expect($shiftData['name'])->toBe('Дневная');
});

/**
 * Тест метода getData.
 * Проверяет, что метод возвращает все 7 ключей данных.
 */
test('getData returns all required keys', function () {
    $workshop = Workshop::factory()->create();

    $service = new RatingBoardDataService;
    $data = $service->getData($workshop->id);

    expect($data)->toHaveKeys([
        'shift',
        'leaders',
        'podium',
        'stickers',
        'statistics',
        'winner',
        'timers',
    ]);
});

/**
 * Тест метода getLeaders с пустым результатом.
 * Проверяет, что при отсутствии заказов возвращается пустой массив.
 */
test('getLeaders returns empty array when no completed orders', function () {
    $workshop = Workshop::factory()->create();
    User::factory()->create(['role_id' => 1]);

    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    expect($leaders)->toBeEmpty();
});
