<?php

namespace Tests\Feature\Services;

use App\Models\Stack;
use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use App\Services\StackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class StackServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_stack_and_max_stack()
    {
        $seamstress = User::factory()->create();
        Stack::factory()->create([
            'seamstress_id' => $seamstress->id,
            'stack' => 5,
            'max' => 10,
        ]);

        StackService::incrementStackAndMaxStack($seamstress->id);

        $this->assertDatabaseHas('stacks', [
            'seamstress_id' => $seamstress->id,
            'stack' => 6,
            'max' => 11,
        ]);
    }

    public function test_reduce_stack()
    {
        $seamstress = User::factory()->create();
        Stack::factory()->create([
            'seamstress_id' => $seamstress->id,
            'stack' => 5,
            'max' => 10,
        ]);

        StackService::reduceStack($seamstress->id);

        $this->assertDatabaseHas('stacks', [
            'seamstress_id' => $seamstress->id,
            'stack' => 4,
            'max' => 10,
        ]);
    }

    public function test_reduce_stack_to_zero_resets_max()
    {
        $seamstress = User::factory()->create();
        Stack::factory()->create([
            'seamstress_id' => $seamstress->id,
            'stack' => 1,
            'max' => 10,
        ]);

        $this->mock(MarketplaceOrderItemService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMaxQuantityOrdersToSeamstress')->andReturn(5);
        });

        StackService::reduceStack($seamstress->id);

        $this->assertDatabaseHas('stacks', [
            'seamstress_id' => $seamstress->id,
            'stack' => 0,
            'max' => 0,
        ]);
    }
}
