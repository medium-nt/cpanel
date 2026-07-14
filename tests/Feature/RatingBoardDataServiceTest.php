<?php

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Schedule;
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
 * Регрессионный тест: позиции швей считаются отдельно, игнорируя других ролей.
 * Баг: если до швей в сортировке были закройщики/ОТК с бóльшим count, позиции сдвигались.
 * Фикс: позиции считаются только для реально добавленных швей ($added), не для всех строк.
 */
test('getLeaders assigns positions starting from 1 ignoring non-seamstresses with higher counts', function () {
    // Arrange: создаём цех и сотрудников разных ролей
    $workshop = Workshop::factory()->create();

    // НЕ-швеи с БОЛЬШИМ count (должны игнорироваться в getLeaders)
    $cutter = User::factory()->create(['role_id' => 4, 'name' => 'Закройщик Топ']);
    $otk = User::factory()->create(['role_id' => 5, 'name' => 'ОТК Топ']);

    // Швеи с МЕНЬШИМ count (должны получить position 1 и 2)
    $seamstressA = User::factory()->create(['role_id' => 1, 'name' => 'Швея А']);
    $seamstressB = User::factory()->create(['role_id' => 1, 'name' => 'Швея Б']);

    $today = Carbon::today()->toDateString().' 12:00:00';

    // Создаём заказы для закройщика (cutting_completed_at) - 5 заказов
    MarketplaceOrderItem::factory()->count(5)->create([
        'workshop_id' => $workshop->id,
        'cutter_id' => $cutter->id,
        'cutting_completed_at' => $today,
        'status' => 3,
    ]);

    // Создаём заказы для ОТК (packed_at) - 4 заказа
    MarketplaceOrderItem::factory()->count(4)->create([
        'workshop_id' => $workshop->id,
        'otk_id' => $otk->id,
        'packed_at' => $today,
        'status' => 3,
    ]);

    // Создаём заказы для швей (completed_at) - меньше, чем у закройщика/ОТК
    MarketplaceOrderItem::factory()->count(2)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressA->id,
        'completed_at' => $today,
        'status' => 3,
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

    // Assert: проверяем, что в ТОЛЬКО швеи (закройщики/ОТК не попали)
    expect($leaders)->toHaveCount(2);

    // Первая швея имеет position 1 (не 3!), medal gold, несмотря на то что
    // в $counts были записи с бóльшим count (закройщик 5, ОТК 4)
    expect($leaders[0])->toMatchArray([
        'id' => $seamstressA->id,
        'name' => 'Швея А',
        'position' => 1,
        'medal' => 'gold',
        'count' => 2,
    ]);

    // Вторая швея имеет position 2 (не 4!), medal silver
    expect($leaders[1])->toMatchArray([
        'id' => $seamstressB->id,
        'name' => 'Швея Б',
        'position' => 2,
        'medal' => 'silver',
        'count' => 1,
    ]);

    // Дополнительная проверка: ID закройщика и ОТК отсутствуют в результатах
    $leaderIds = collect($leaders)->pluck('id')->toArray();
    expect($leaderIds)->not->toContain($cutter->id);
    expect($leaderIds)->not->toContain($otk->id);
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
 * Тест метода getStatistics для швеи с заказами за вчера.
 * Проверяет, что создаётся запись с датой вчерашнего дня, значением и медалью.
 */
test('getStatistics creates record for seamstress with orders from yesterday', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Швея Анна']);
    $shift = Shift::factory()->create(['name' => 'Дневная']);

    // Создаём расписание на вчерашний день
    Schedule::factory()->create([
        'user_id' => $seamstress->id,
        'date' => Carbon::yesterday()->toDateString(),
        'shift_id' => $shift->id,
    ]);

    // Создаём 2 заказа, выполненных вчера
    MarketplaceOrderItem::factory()->count(2)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::yesterday()->toDateString().' 14:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0])->toMatchArray([
        'name' => 'Швея Анна',
        'profession' => 'Швея',
        'value' => Carbon::yesterday()->format('d.m.y').' выполнено 2 заказ(ов)!',
        'shift' => 'Дневная',
        'medal' => 'gold',
    ]);
});

/**
 * Тест метода getStatistics: сегодняшние заказы НЕ включаются.
 * Период статистики — с начала месяца до ВЧЕРА (не включая сегодня).
 */
test('getStatistics excludes orders from today', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1]);

    // Заказы сегодня (НЕ должны включаться)
    MarketplaceOrderItem::factory()->count(3)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::today()->toDateString().' 10:00:00',
        'status' => 3,
    ]);

    // Заказы вчера (должны включаться)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::yesterday()->toDateString().' 15:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['value'])->toContain(Carbon::yesterday()->format('d.m.y'));
    expect($statistics[0]['value'])->toContain('выполнено 1 заказ(ов)!');
});

/**
 * Тест метода getStatistics для всех трёх ролей.
 * Проверяет, что швеи, закройщики и ОТК создают записи по своим колонкам дат.
 */
test('getStatistics creates records for all three roles', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Швея']);
    $cutter = User::factory()->create(['role_id' => 4, 'name' => 'Закройщик']);
    $otk = User::factory()->create(['role_id' => 5, 'name' => 'ОТК']);
    $yesterday = Carbon::yesterday()->toDateString();

    // Заказы для швеи (completed_at)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 10:00:00',
        'status' => 3,
    ]);

    // Заказы для закройщика (cutting_completed_at)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'cutter_id' => $cutter->id,
        'cutting_completed_at' => $yesterday.' 11:00:00',
        'status' => 3,
    ]);

    // Заказы для ОТК (packed_at)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'otk_id' => $otk->id,
        'packed_at' => $yesterday.' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(3);

    $professions = collect($statistics)->pluck('profession')->sort()->values()->toArray();
    expect($professions)->toBe(['Закройщик', 'Сотрудник ОТК', 'Швея']);
});

/**
 * Тест метода getStatistics: медаль gold только у швей-рекордсменов.
 * Закройщики и ОТК не получают медали даже с большим количеством заказов.
 */
test('getStatistics gold medal only for seamstress record holders', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Швея']);
    $cutter = User::factory()->create(['role_id' => 4, 'name' => 'Закройщик']);
    $otk = User::factory()->create(['role_id' => 5, 'name' => 'ОТК']);
    $yesterday = Carbon::yesterday()->toDateString();

    // Швея: 1 заказ → получает gold (рекорд дня среди швей)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 10:00:00',
        'status' => 3,
    ]);

    // Закройщик: 5 заказов → medal = null (не швея)
    MarketplaceOrderItem::factory()->count(5)->create([
        'workshop_id' => $workshop->id,
        'cutter_id' => $cutter->id,
        'cutting_completed_at' => $yesterday.' 11:00:00',
        'status' => 3,
    ]);

    // ОТК: 3 заказа → medal = null (не швея)
    MarketplaceOrderItem::factory()->count(3)->create([
        'workshop_id' => $workshop->id,
        'otk_id' => $otk->id,
        'packed_at' => $yesterday.' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(3);

    $seamstressRecord = collect($statistics)->first(fn ($r) => $r['profession'] === 'Швея');
    $cutterRecord = collect($statistics)->first(fn ($r) => $r['profession'] === 'Закройщик');
    $otkRecord = collect($statistics)->first(fn ($r) => $r['profession'] === 'Сотрудник ОТК');

    expect($seamstressRecord['medal'])->toBe('gold');
    expect($cutterRecord['medal'])->toBeNull();
    expect($otkRecord['medal'])->toBeNull();
});

/**
 * Тест метода getStatistics: две швеи в один день.
 * Рекордсмен получает gold, вторая — null. При равенстве — обе получают gold.
 */
test('getStatistics gold medal for day record holder among seamstresses', function () {
    $workshop = Workshop::factory()->create();
    $seamstressA = User::factory()->create(['role_id' => 1, 'name' => 'Швея А']);
    $seamstressB = User::factory()->create(['role_id' => 1, 'name' => 'Швея Б']);
    $yesterday = Carbon::yesterday()->toDateString();

    // Швея А: 3 заказа → gold (рекорд дня)
    MarketplaceOrderItem::factory()->count(3)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressA->id,
        'completed_at' => $yesterday.' 10:00:00',
        'status' => 3,
    ]);

    // Швея Б: 1 заказ → null (не рекорд)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressB->id,
        'completed_at' => $yesterday.' 11:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(2);

    $recordA = collect($statistics)->first(fn ($r) => $r['name'] === 'Швея А');
    $recordB = collect($statistics)->first(fn ($r) => $r['name'] === 'Швея Б');

    expect($recordA['medal'])->toBe('gold');
    expect($recordA['value'])->toContain('выполнено 3 заказ(ов)!');
    expect($recordB['medal'])->toBeNull();
    expect($recordB['value'])->toContain('выполнено 1 заказ(ов)!');
});

/**
 * Тест метода getStatistics: две швеи с равным количеством заказов.
 * При равенстве рекорда дня обе получают gold (строгое сравнение ===).
 */
test('getStatistics both seamstresses get gold with equal daily records', function () {
    $workshop = Workshop::factory()->create();
    $seamstressA = User::factory()->create(['role_id' => 1, 'name' => 'Швея А']);
    $seamstressB = User::factory()->create(['role_id' => 1, 'name' => 'Швея Б']);
    $yesterday = Carbon::yesterday()->toDateString();

    // Обе швеи: по 2 заказа → обе получают gold
    MarketplaceOrderItem::factory()->count(2)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressA->id,
        'completed_at' => $yesterday.' 10:00:00',
        'status' => 3,
    ]);

    MarketplaceOrderItem::factory()->count(2)->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstressB->id,
        'completed_at' => $yesterday.' 11:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(2);

    $recordA = collect($statistics)->first(fn ($r) => $r['name'] === 'Швея А');
    $recordB = collect($statistics)->first(fn ($r) => $r['name'] === 'Швея Б');

    expect($recordA['medal'])->toBe('gold');
    expect($recordB['medal'])->toBe('gold');
});

/**
 * Тест метода getStatistics: смена из расписания schedules.
 * Проверяет подстановку shift.name по ключу "user_id|date", при отсутствии — пустая строка.
 */
test('getStatistics gets shift from schedules by user and date', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1]);
    $shift = Shift::factory()->create(['name' => 'Утренняя']);
    $yesterday = Carbon::yesterday()->toDateString();

    // Расписание на вчерашний день
    Schedule::factory()->create([
        'user_id' => $seamstress->id,
        'date' => $yesterday,
        'shift_id' => $shift->id,
    ]);

    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['shift'])->toBe('Утренняя');
});

/**
 * Тест метода getStatistics: отсутствие расписания → пустая смена.
 */
test('getStatistics returns empty shift when no schedule exists', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1]);
    $yesterday = Carbon::yesterday()->toDateString();

    // Расписание НЕ создаём
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['shift'])->toBe('');
});

/**
 * Тест метода getStatistics: фильтрация по workshop_id.
 * Заказы других цехов не попадают в статистику.
 */
test('getStatistics filters by workshop_id', function () {
    $workshopA = Workshop::factory()->create();
    $workshopB = Workshop::factory()->create();
    $seamstressA = User::factory()->create(['role_id' => 1, 'name' => 'Швея А']);
    $seamstressB = User::factory()->create(['role_id' => 1, 'name' => 'Швея Б']);
    $yesterday = Carbon::yesterday()->toDateString();

    // Заказы цеха A (должны включаться)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshopA->id,
        'seamstress_id' => $seamstressA->id,
        'completed_at' => $yesterday.' 10:00:00',
        'status' => 3,
    ]);

    // Заказы цеха B (НЕ должны включаться)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshopB->id,
        'seamstress_id' => $seamstressB->id,
        'completed_at' => $yesterday.' 11:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshopA->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['name'])->toBe('Швея А');
});

/**
 * Тест метода getStatistics: группировка по дням.
 * Два заказа одной швеи в один день = одна запись с count=2 (не две записи).
 */
test('getStatistics groups orders by day not by individual orders', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1]);
    $yesterday = Carbon::yesterday()->toDateString();

    // 3 заказа одной швеи вчера в разное время
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 09:00:00',
        'status' => 3,
    ]);

    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 11:00:00',
        'status' => 3,
    ]);

    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $yesterday.' 15:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    // Одна запись с count=3, не три записи с count=1
    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['value'])->toContain('выполнено 3 заказ(ов)!');
});

/**
 * Тест метода getStatistics: заказы прошлого месяца не учитываются.
 * Период — только текущий месяц (startOfMonth → yesterday).
 */
test('getStatistics excludes orders from previous month', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1]);

    // Заказ прошлого месяца (НЕ должен включаться)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::now()->subMonth()->toDateString().' 12:00:00',
        'status' => 3,
    ]);

    // Заказ текущего месяца (должен включаться)
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => Carbon::yesterday()->toDateString().' 12:00:00',
        'status' => 3,
    ]);

    $service = new RatingBoardDataService;
    $statistics = $service->getStatistics($workshop->id);

    expect($statistics)->toHaveCount(1);
    expect($statistics[0]['value'])->toContain(Carbon::yesterday()->format('d.m.y'));
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

/**
 * Тест поля shift_done: открыл и закрыл смену сегодня → shift_done === true.
 * Проверяет, что у швеи с выполненными заказами и закрытой сменой поле shift_done=true.
 */
test('getLeaders marks shift_done as true when seamstress opened and closed shift today', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Швея с закрытой сменой']);

    // Создаём выполненный заказ сегодня (чтобы швея попала в getLeaders)
    $today = Carbon::today()->toDateString().' 12:00:00';
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $today,
        'status' => 3,
    ]);

    // Создаём расписание: открыта И закрыта смена сегодня
    $schedule = new Schedule;
    $schedule->user_id = $seamstress->id;
    $schedule->date = Carbon::today()->toDateString();
    $schedule->shift_opened_time = '08:00:00';
    $schedule->shift_closed_time = '17:00:00';
    $schedule->save();

    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    expect($leaders)->toHaveCount(1);
    expect($leaders[0])->toMatchArray([
        'id' => $seamstress->id,
        'name' => 'Швея с закрытой сменой',
        'shift_done' => true,
    ]);
});

/**
 * Тест поля shift_done: открыл но НЕ закрыл смену → shift_done === false.
 * Проверяет, что при открытии смены без закрытия поле shift_done=false.
 */
test('getLeaders marks shift_done as false when seamstress opened but not closed shift', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Швея без закрытия']);

    // Создаём выполненный заказ сегодня
    $today = Carbon::today()->toDateString().' 12:00:00';
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $today,
        'status' => 3,
    ]);

    // Создаём расписание: открыта смена, но НЕ закрыта (shift_closed_time = '00:00:00')
    $schedule = new Schedule;
    $schedule->user_id = $seamstress->id;
    $schedule->date = Carbon::today()->toDateString();
    $schedule->shift_opened_time = '08:00:00';
    $schedule->shift_closed_time = '00:00:00';
    $schedule->save();

    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    expect($leaders)->toHaveCount(1);
    expect($leaders[0])->toMatchArray([
        'id' => $seamstress->id,
        'name' => 'Швея без закрытия',
        'shift_done' => false,
    ]);
});

/**
 * Тест поля shift_done: нет записи в schedules → shift_done === false.
 * Проверяет, что при отсутствии расписания на сегодня поле shift_done=false.
 */
test('getLeaders marks shift_done as false when no schedule record exists', function () {
    $workshop = Workshop::factory()->create();
    $seamstress = User::factory()->create(['role_id' => 1, 'name' => 'Швея без расписания']);

    // Создаём выполненный заказ сегодня
    $today = Carbon::today()->toDateString().' 12:00:00';
    MarketplaceOrderItem::factory()->create([
        'workshop_id' => $workshop->id,
        'seamstress_id' => $seamstress->id,
        'completed_at' => $today,
        'status' => 3,
    ]);

    // Расписание НЕ создаём

    $service = new RatingBoardDataService;
    $leaders = $service->getLeaders($workshop->id);

    expect($leaders)->toHaveCount(1);
    expect($leaders[0])->toMatchArray([
        'id' => $seamstress->id,
        'name' => 'Швея без расписания',
        'shift_done' => false,
    ]);
});
