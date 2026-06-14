<?php

namespace Tests\Feature\Services;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Services\RollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RollServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getLowMaterialRolls method returns rolls with low material quantity.
     */
    public function test_get_low_material_rolls_returns_rolls_with_quantity_below_threshold(): void
    {
        // Arrange
        $material1 = Material::factory()->create();
        $material2 = Material::factory()->create();

        // Create rolls with different quantities
        $lowQuantityRoll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material1->id,
            'initial_quantity' => 10,
        ]);

        $highQuantityRoll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material2->id,
            'initial_quantity' => 100,
        ]);

        // Create movement material to consume quantity
        MovementMaterial::factory()->create([
            'roll_id' => $lowQuantityRoll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6, // 10 - 6 = 4, which is below the threshold of 5
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertEquals($lowQuantityRoll->id, $lowMaterialRolls->first()->id);
        $this->assertEquals(4, $lowMaterialRolls->first()->computed_quantity);
    }

    /**
     * Test getLowMaterialRolls method returns empty collection when no rolls have low material.
     */
    public function test_get_low_material_rolls_returns_empty_collection_when_no_low_rolls(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 100,
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(0, $lowMaterialRolls);
    }

    /**
     * Test getLowMaterialRolls method returns rolls ordered by computed quantity ascending.
     */
    public function test_get_low_material_rolls_orders_results_by_computed_quantity_ascending(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll1 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        $roll2 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        // Create movement materials to set different remaining quantities
        MovementMaterial::factory()->create([
            'roll_id' => $roll1->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 8, // 10 - 8 = 2
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll2->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 7, // 10 - 7 = 3
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert - both rolls should be under threshold
        $this->assertCount(2, $lowMaterialRolls);
        $this->assertEquals(2, $lowMaterialRolls->first()->computed_quantity);
        $this->assertEquals(3, $lowMaterialRolls->last()->computed_quantity);
    }

    /**
     * Test getLowMaterialRollsCount method returns correct count of low material rolls.
     */
    public function test_get_low_material_rolls_count_returns_correct_count(): void
    {
        // Arrange
        $material1 = Material::factory()->create();
        $material2 = Material::factory()->create();

        $roll1 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material1->id,
            'initial_quantity' => 10,
        ]);

        $roll2 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material2->id,
            'initial_quantity' => 10,
        ]);

        $roll3 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material1->id,
            'initial_quantity' => 100,
        ]);

        // Create movement materials to make rolls 1 and 2 have low quantity
        MovementMaterial::factory()->create([
            'roll_id' => $roll1->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll2->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6,
        ]);

        // Act
        $count = RollService::getLowMaterialRollsCount();

        // Assert
        $this->assertEquals(2, $count);
    }

    /**
     * Test getLowMaterialRollsCount method returns 0 when no low material rolls exist.
     */
    public function test_get_low_material_rolls_count_returns_zero_when_no_low_rolls(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 100,
        ]);

        // Act
        $count = RollService::getLowMaterialRollsCount();

        // Assert
        $this->assertEquals(0, $count);
    }

    /**
     * Test lowMaterialRollsQuery includes computed_quantity in results.
     */
    public function test_low_material_rolls_query_includes_computed_quantity(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6, // 10 - 6 = 4
        ]);

        // Act
        $query = RollService::lowMaterialRollsQuery();
        $results = $query->get();

        // Assert
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('computed_quantity', $results->first()->toArray());
        $this->assertEquals(4, $results->first()->computed_quantity);
    }

    /**
     * Test lowMaterialRollsQuery joins with materials table.
     */
    public function test_low_material_rolls_query_joins_with_materials(): void
    {
        // Arrange
        $material1 = Material::factory()->create(['type_id' => 1]);
        $material2 = Material::factory()->create(['type_id' => 2]);

        $roll1 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material1->id,
            'initial_quantity' => 10,
        ]);

        $roll2 = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material2->id,
            'initial_quantity' => 10,
        ]);

        // Create movement materials to make both rolls have low quantity
        MovementMaterial::factory()->create([
            'roll_id' => $roll1->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll2->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6,
        ]);

        // Act - filter by type_id = 1
        $query = RollService::lowMaterialRollsQuery(null, 1);
        $results = $query->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($material1->id, $results->first()->material_id);
        $this->assertEquals(1, $results->first()->material->type_id);
    }

    /**
     * Test that rolls with exactly the threshold value are included.
     */
    public function test_low_material_rolls_includes_rolls_at_threshold(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 5, // Threshold is 5
        ]);

        // Act - no movement materials, so quantity = 5 (threshold)
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertEquals(5, $lowMaterialRolls->first()->computed_quantity);
    }

    /**
     * Test that completed rolls are not included in low material rolls.
     */
    public function test_completed_rolls_are_not_included_in_low_material_rolls(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $completedRoll = Roll::factory()->create([
            'status' => Roll::STATUS_COMPLETED,
            'material_id' => $material->id,
            'initial_quantity' => 10,
            'completed_at' => now(),
        ]);

        // Create movement materials to make it have low quantity
        MovementMaterial::factory()->create([
            'roll_id' => $completedRoll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6, // 10 - 6 = 4, which is below threshold
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(0, $lowMaterialRolls);
    }

    /**
     * Test that rolls in storage are not included in low material rolls.
     */
    public function test_rolls_in_storage_are_not_included_in_low_material_rolls(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $storageRoll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_STORAGE,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        // Create movement materials to make it have low quantity
        MovementMaterial::factory()->create([
            'roll_id' => $storageRoll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6, // 10 - 6 = 4, which is below threshold
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(0, $lowMaterialRolls);
    }

    /**
     * Test that rolls shipped to workshop are not included in low material rolls.
     */
    public function test_rolls_shipped_to_workshop_are_not_included_in_low_material_rolls(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $shippedRoll = Roll::factory()->create([
            'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        // Create movement materials to make it have low quantity
        MovementMaterial::factory()->create([
            'roll_id' => $shippedRoll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6, // 10 - 6 = 4, which is below threshold
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(0, $lowMaterialRolls);
    }

    /**
     * Test that low material rolls are correctly loaded with material relationship.
     */
    public function test_low_material_rolls_include_material_relationship(): void
    {
        // Arrange
        $material = Material::factory()->create(['title' => 'Test Material']);

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6,
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertNotNull($lowMaterialRolls->first()->material);
        $this->assertEquals('Test Material', $lowMaterialRolls->first()->material->title);
    }

    /**
     * Test that movement materials with type_movement = 3 are included in quantity calculation.
     */
    public function test_type_movement_3_included_in_quantity_calculation(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 6, // 10 - 6 = 4
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertEquals(4, $lowMaterialRolls->first()->computed_quantity);
    }

    /**
     * Test that movement materials with type_movement = 4 are included in quantity calculation.
     */
    public function test_type_movement_4_included_in_quantity_calculation(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 10,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 4]),
            'quantity' => 6, // 10 - 6 = 4
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertEquals(4, $lowMaterialRolls->first()->computed_quantity);
    }

    /**
     * Test that movement materials with other type_movement values are not included in quantity calculation.
     */
    public function test_other_type_movement_not_included_in_quantity_calculation(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 5, // Exactly at threshold
        ]);

        // Create movement materials with other types (not 3 or 4)
        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 1]),
            'quantity' => 2, // Should not be included, remaining: 3
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 2]),
            'quantity' => 1, // Should not be included, remaining: 2
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert - should still be in results since type 1 and 2 don't count
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertEquals(5, $lowMaterialRolls->first()->computed_quantity); // 5 - 0 (since type 1&2 don't count) = 5
    }

    /**
     * Test that multiple movement materials of type 3 and 4 are summed correctly.
     */
    public function test_multiple_movement_materials_of_types_3_and_4_are_summed(): void
    {
        // Arrange
        $material = Material::factory()->create();

        $roll = Roll::factory()->create([
            'status' => Roll::STATUS_IN_WORKSHOP,
            'material_id' => $material->id,
            'initial_quantity' => 15,
        ]);

        // Create multiple movement materials of type 3 and 4
        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 5,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 4]),
            'quantity' => 3,
        ]);

        MovementMaterial::factory()->create([
            'roll_id' => $roll->id,
            'order_id' => Order::factory()->create(['type_movement' => 3]),
            'quantity' => 2,
        ]);

        // Act
        $lowMaterialRolls = RollService::getLowMaterialRolls();

        // Assert
        $expectedRemaining = 15 - (5 + 3 + 2); // 15 - 10 = 5
        $this->assertCount(1, $lowMaterialRolls);
        $this->assertEquals($expectedRemaining, $lowMaterialRolls->first()->computed_quantity);
    }
}
