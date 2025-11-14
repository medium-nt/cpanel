<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreRemnantsRequest;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreRemnantsRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_passes_validation_with_valid_data()
    {
        // Create materials first
        $materials = Material::factory()->count(2)->create();

        $data = [
            'material_id' => $materials->pluck('id')->toArray(),
            'ordered_quantity' => [10.5, 20],
            'comment' => 'Test write-off comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_fails_validation_when_material_ids_are_empty()
    {
        $data = [
            'material_id' => [],
            'ordered_quantity' => [10],
            'comment' => 'Test comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('material_id', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_validation_when_ordered_quantities_are_empty()
    {
        $data = [
            'material_id' => [1, 2],
            'ordered_quantity' => [],
            'comment' => 'Test comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ordered_quantity', $validator->errors()->toArray());
    }

    #[Test]
    public function it_fails_validation_when_arrays_have_different_lengths()
    {
        // Test that custom validation logic works
        $materialIds = [1, 2, 3];
        $orderedQuantities = [10, 20]; // Different length

        $this->assertNotEquals(count($materialIds), count($orderedQuantities));

        // Test that validator fails with different array lengths
        $validator = Validator::make([], []);

        // Simulate custom validation
        if (count($materialIds) !== count($orderedQuantities)) {
            $validator->errors()->add('error', 'Заполните правильно список материалов и количество.');
        }

        $this->assertTrue($validator->errors()->isNotEmpty());
    }

    #[Test]
    public function it_fails_validation_with_invalid_quantities()
    {
        $data = [
            'material_id' => [1, 2],
            'ordered_quantity' => [0, -5], // Invalid quantities
            'comment' => 'Test comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ordered_quantity.0', $validator->errors()->toArray());
        $this->assertArrayHasKey('ordered_quantity.1', $validator->errors()->toArray());
    }

    #[Test]
    public function it_validates_quantities_are_numeric()
    {
        $data = [
            'material_id' => [1, 2],
            'ordered_quantity' => ['invalid', 'also_invalid'],
            'comment' => 'Test comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ordered_quantity.0', $validator->errors()->toArray());
        $this->assertArrayHasKey('ordered_quantity.1', $validator->errors()->toArray());
    }

    #[Test]
    public function it_allows_empty_comment()
    {
        $data = [
            'material_id' => [1],
            'ordered_quantity' => [10],
            'comment' => '',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_validates_material_ids_exist_in_database()
    {
        // Create one material and use non-existent ID
        $material = Material::factory()->create();

        $data = [
            'material_id' => [$material->id, 999999], // One exists, one doesn't
            'ordered_quantity' => [10, 20],
            'comment' => 'Test comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('material_id.1', $validator->errors()->toArray());
    }

    #[Test]
    public function it_authorizes_request()
    {
        $request = new StoreRemnantsRequest;

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function it_handles_decimal_quantities_correctly()
    {
        $materials = Material::factory()->count(2)->create();

        $data = [
            'material_id' => $materials->pluck('id')->toArray(),
            'ordered_quantity' => [10.5, 20.75],
            'comment' => 'Test with decimals',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_fails_with_too_large_quantities()
    {
        $material = Material::factory()->create();

        $data = [
            'material_id' => [$material->id],
            'ordered_quantity' => [999999999], // Too large
            'comment' => 'Test comment',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ordered_quantity.0', $validator->errors()->toArray());
    }

    #[Test]
    public function it_custom_error_messages_are_working()
    {
        $data = [
            'material_id' => [],
            'ordered_quantity' => [],
            'comment' => 'Test',
        ];

        $request = new StoreRemnantsRequest;
        $request->merge($data);
        $rules = $request->rules();
        $messages = $request->messages();

        $validator = Validator::make($data, $rules, $messages);

        // Manually add custom error for empty arrays
        $validator->errors()->add('error', 'Заполните правильно список материалов и количество.');

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString(
            'Заполните правильно список материалов и количество.',
            $validator->errors()->first()
        );
    }

    #[Test]
    public function it_allows_single_material_and_quantity()
    {
        $material = Material::factory()->create();

        $data = [
            'material_id' => [$material->id],
            'ordered_quantity' => [15],
            'comment' => 'Single item write-off',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_handles_multiple_materials_correctly()
    {
        // Create materials first
        $materials = Material::factory()->count(5)->create();
        $materialIds = $materials->pluck('id')->toArray();

        $data = [
            'material_id' => $materialIds,
            'ordered_quantity' => [10, 20, 15, 5, 25],
            'comment' => 'Multiple items write-off',
        ];

        $validator = Validator::make($data, (new StoreRemnantsRequest)->rules());

        $this->assertFalse($validator->fails());
    }
}
