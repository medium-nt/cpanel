<?php

namespace Tests\Feature\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getFiltered method with default parameters.
     *
     * @return void
     */
    public function test_get_filtered_with_default_parameters(): void
    {
        // Arrange
        Order::factory()->count(3)->create(['status' => 0, 'type_movement' => 4]);
        Order::factory()->count(2)->create(['status' => 1, 'type_movement' => 7]);
        Order::factory()->count(1)->create(['status' => -1, 'type_movement' => 4]);
        Order::factory()->count(1)->create(['status' => 3, 'type_movement' => 7]);
        Order::factory()->count(1)->create(['status' => 0, 'type_movement' => 1]);

        $request = new Request();

        // Act
        $filteredOrders = OrderService::getFiltered($request)->get();

        // Assert
        $this->assertCount(5, $filteredOrders);
    }

    /**
     * Test filtering by status.
     *
     * @return void
     */
    public function test_get_filtered_by_status(): void
    {
        // Arrange
        Order::factory()->create(['status' => 3, 'type_movement' => 4]);
        Order::factory()->create(['status' => 0, 'type_movement' => 7]);

        $request = new Request([
            'status' => '3',
        ]);

        // Act
        $filteredOrders = OrderService::getFiltered($request)->get();

        // Assert
        $this->assertCount(1, $filteredOrders);
        $this->assertEquals(3, $filteredOrders->first()->status);
    }

    /**
     * Test filtering by type_movement.
     *
     * @return void
     */
    public function test_get_filtered_by_type_movement(): void
    {
        // Arrange
        Order::factory()->create(['type_movement' => 7, 'status' => 1]);
        Order::factory()->create(['type_movement' => 4, 'status' => 0]);

        $request = new Request([
            'type_movement' => 7,
        ]);

        // Act
        $filteredOrders = OrderService::getFiltered($request)->get();

        // Assert
        $this->assertCount(1, $filteredOrders);
        $this->assertEquals(7, $filteredOrders->first()->type_movement);
    }

    /**
     * Test filtering by user ID.
     *
     * @return void
     */
    public function test_get_filtered_by_user_id(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::factory()->create([
            'seamstress_id' => $user1->id,
            'type_movement' => 4,
            'status' => 0
        ]);
        Order::factory()->create([
            'cutter_id' => $user1->id,
            'type_movement' => 7,
            'status' => 1
        ]);
        Order::factory()->create([
            'seamstress_id' => $user2->id,
            'cutter_id' => $user2->id,
            'type_movement' => 4,
            'status' => 0
        ]);

        $request = new Request([
            'users_id' => $user1->id,
        ]);

        // Act
        $filteredOrders = OrderService::getFiltered($request)->get();

        // Assert
        $this->assertCount(2, $filteredOrders);
    }

    /**
     * Test filtering by date range.
     *
     * @return void
     */
    public function test_get_filtered_by_date_range(): void
    {
        // Arrange
        Order::factory()->create(['created_at' => now()->subDays(5), 'type_movement' => 4, 'status' => 0]);
        Order::factory()->create(['created_at' => now()->subDays(2), 'type_movement' => 7, 'status' => 1]);
        Order::factory()->create(['created_at' => now(), 'type_movement' => 4, 'status' => 0]);

        $request = new Request([
            'date_start' => now()->subDays(3)->toDateString(),
            'date_end' => now()->subDays(1)->toDateString(),
        ]);

        // Act
        $filteredOrders = OrderService::getFiltered($request)->get();

        // Assert
        $this->assertCount(1, $filteredOrders);
    }
}
