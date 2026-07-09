<?php

namespace Tests\Feature\Services;

use App\Services\GazelkaApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GazelkaApiServiceTest extends TestCase
{
    use RefreshDatabase;

    private GazelkaApiService $service;

    private string $baseUrl;

    private string $testToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = config('services.gazelka.base_url');
        $this->testToken = 'test-token';

        config(['services.gazelka.token' => $this->testToken]);

        $this->service = new GazelkaApiService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    #[Test]
    public function descriptions_returns_data_on_success()
    {
        $responseData = [
            'statuses' => [
                ['id' => 1, 'name' => 'Новая'],
                ['id' => 2, 'name' => 'В работе'],
            ],
            'marketplaces' => [
                ['id' => 1, 'name' => 'Ozon'],
                ['id' => 2, 'name' => 'Wildberries'],
            ],
        ];

        Http::fake(["{$this->baseUrl}/descriptions" => Http::response($responseData, 200)]);

        $result = $this->service->descriptions();

        $this->assertNotNull($result);
        $this->assertEquals($responseData['statuses'][0]['name'], $result->statuses[0]->name);

        Http::assertSent(function ($request) {
            return $request->url() === "{$this->baseUrl}/descriptions"
                && $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer '.$this->testToken)
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    #[Test]
    public function schedule_returns_data_on_success()
    {
        $pricelistId = GazelkaApiService::CITY_IVANOVO;
        $responseData = [
            'schedule' => [
                '2026-06-18' => ['10:00-12:00', '14:00-16:00'],
                '2026-06-19' => ['09:00-11:00'],
            ],
        ];

        Http::fake(["{$this->baseUrl}/schedule" => Http::response($responseData, 200)]);

        $result = $this->service->schedule($pricelistId);

        $this->assertNotNull($result);

        Http::assertSent(function ($request) use ($pricelistId) {
            return $request->url() === "{$this->baseUrl}/schedule"
                && $request->method() === 'POST'
                && $request['pricelist_id'] === $pricelistId;
        });
    }

    #[Test]
    public function new_plan_returns_success_response()
    {
        $requestData = [
            'pallets' => 0,
            'boxes' => 1,
            'cargo_pickup' => 1,
            'palleting' => 1,
            'departure_address' => 'Станкостроителей 8',
            'departure_date' => '2026-06-18',
            'departure_time' => '14:00-16:00',
            'marketplace_id' => 1,
            'place_id' => 29,
            'delivery_date' => '2026-06-22',
            'monomix' => GazelkaApiService::SUPPLY_TYPE_MONO,
            'notes' => 'тест',
            'supply_id' => 1,
            'weight2' => 2,
            'length' => 30,
            'width' => 25,
            'height' => 20,
        ];

        $responseData = [
            'status' => 'success',
            'message' => 'Заявка 82790 успешно создана!',
        ];

        Http::fake(["{$this->baseUrl}/new-plan" => Http::response($responseData, 200)]);

        $result = $this->service->newPlan($requestData);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result->status);
        $this->assertStringContainsString('82790', $result->message);

        Http::assertSent(function ($request) use ($requestData) {
            return $request->url() === "{$this->baseUrl}/new-plan"
                && $request->method() === 'POST'
                && $request['pallets'] === $requestData['pallets']
                && $request['departure_address'] === $requestData['departure_address']
                && $request['monomix'] === $requestData['monomix'];
        });
    }

    #[Test]
    public function delete_plan_returns_success_response()
    {
        $planId = 82790;
        $responseData = [
            'status' => 'success',
            'message' => 'План удалён',
        ];

        Http::fake(["{$this->baseUrl}/delete-plan" => Http::response($responseData, 200)]);

        $result = $this->service->deletePlan($planId);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result->status);

        Http::assertSent(function ($request) use ($planId) {
            return $request->url() === "{$this->baseUrl}/delete-plan"
                && $request->method() === 'POST'
                && $request['plan_id'] === $planId;
        });
    }

    #[Test]
    public function my_plans_returns_list_of_plans()
    {
        $responseData = [
            'plans' => [
                ['id' => 82790, 'status' => 'active', 'date' => '2026-06-18'],
                ['id' => 82791, 'status' => 'pending', 'date' => '2026-06-19'],
            ],
        ];

        Http::fake(["{$this->baseUrl}/my-plans" => Http::response($responseData, 200)]);

        $result = $this->service->myPlans();

        $this->assertNotNull($result);
        $this->assertCount(2, $result->plans);

        Http::assertSent(function ($request) {
            return $request->url() === "{$this->baseUrl}/my-plans"
                && $request->method() === 'POST';
        });
    }

    #[Test]
    public function create_pickup_returns_success_response()
    {
        $requestData = [
            'selected_plans' => [257278],
            'pickup_town' => 'Иваново',
            'pickup_street' => 'ул. Ленина, д. 1',
            'pickup_date' => '2025-12-20',
            'pickup_time' => '10:00-12:00',
            'pickup_contact' => 'Иван Иванов, +79991234567',
        ];

        $responseData = [
            'status' => 'success',
            'message' => 'Заявка на забор груза создана.',
            'pickup_id' => 3231,
            'boxes' => 1,
            'pallets' => 0,
            'plan_ids' => [257278],
        ];

        Http::fake(["{$this->baseUrl}/create-pickup" => Http::response($responseData, 200)]);

        $result = $this->service->createPickup($requestData);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result->status);
        $this->assertEquals(3231, $result->pickup_id);

        Http::assertSent(function ($request) use ($requestData) {
            return $request->url() === "{$this->baseUrl}/create-pickup"
                && $request->method() === 'POST'
                && $request['selected_plans'] === $requestData['selected_plans']
                && $request['pickup_town'] === $requestData['pickup_town']
                && $request['pickup_contact'] === $requestData['pickup_contact'];
        });
    }

    #[Test]
    public function add_to_pickup_returns_success_response()
    {
        $pickupId = 3231;
        $planId = 257279;
        $responseData = [
            'status' => 'success',
            'message' => 'Заявка добавлена к забору',
        ];

        Http::fake(["{$this->baseUrl}/add-to-pickup" => Http::response($responseData, 200)]);

        $result = $this->service->addToPickup($pickupId, $planId);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result->status);

        Http::assertSent(function ($request) use ($pickupId, $planId) {
            return $request->url() === "{$this->baseUrl}/add-to-pickup"
                && $request->method() === 'POST'
                && $request['pickup_id'] === $pickupId
                && $request['plan_id'] === $planId;
        });
    }

    #[Test]
    public function remove_from_pickup_returns_success_response()
    {
        $pickupId = 3231;
        $plansToRemove = [257278, 257279];
        $responseData = [
            'status' => 'success',
            'message' => 'Заявки откреплены от забора',
        ];

        Http::fake(["{$this->baseUrl}/remove-from-pickup" => Http::response($responseData, 200)]);

        $result = $this->service->removeFromPickup($pickupId, $plansToRemove);

        $this->assertNotNull($result);
        $this->assertEquals('success', $result->status);

        Http::assertSent(function ($request) use ($pickupId, $plansToRemove) {
            return $request->url() === "{$this->baseUrl}/remove-from-pickup"
                && $request->method() === 'POST'
                && $request['pickup_id'] === $pickupId
                && $request['plans_to_remove'] === $plansToRemove;
        });
    }

    #[Test]
    public function pricelist_returns_data_with_all_parameters()
    {
        $type = GazelkaApiService::PAYMENT_CASHLESS;
        $weekday = 3;
        $responseData = [
            'prices' => [
                ['city' => 'Иваново', 'price' => 1500],
                ['city' => 'Кострома', 'price' => 1800],
            ],
        ];

        Http::fake(["{$this->baseUrl}/pricelist" => Http::response($responseData, 200)]);

        $result = $this->service->pricelist($type, $weekday);

        $this->assertNotNull($result);

        Http::assertSent(function ($request) use ($type, $weekday) {
            return $request->url() === "{$this->baseUrl}/pricelist"
                && $request->method() === 'POST'
                && $request['type'] === $type
                && $request['weekday'] === $weekday;
        });
    }

    #[Test]
    public function pricelist_filters_null_parameters()
    {
        $responseData = ['prices' => []];

        Http::fake(["{$this->baseUrl}/pricelist" => Http::response($responseData, 200)]);

        $result = $this->service->pricelist(null, null);

        $this->assertNotNull($result);

        Http::assertSent(function ($request) {
            return $request->url() === "{$this->baseUrl}/pricelist"
                && $request->method() === 'POST'
                && ! isset($request['type'])
                && ! isset($request['weekday']);
        });
    }

    #[Test]
    public function descriptions_returns_null_on_http_500_error()
    {
        Http::fake(["{$this->baseUrl}/descriptions" => Http::response(['error' => 'Internal Server Error'], 500)]);

        $result = $this->service->descriptions();

        $this->assertNull($result);
    }

    #[Test]
    public function my_plans_returns_null_on_http_404_error()
    {
        Http::fake(["{$this->baseUrl}/my-plans" => Http::response(['error' => 'Not Found'], 404)]);

        $result = $this->service->myPlans();

        $this->assertNull($result);
    }

    #[Test]
    public function delete_plan_returns_null_on_connection_exception()
    {
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('Unable to connect');
        });

        $result = $this->service->deletePlan(12345);

        $this->assertNull($result);
    }
}
