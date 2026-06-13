<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Tariff;
use App\Models\UserTariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tariff>
 */
class TariffFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tariff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_tariff_id' => UserTariff::factory(),
            'material_id' => Material::factory(),
            'range' => null,
            'width' => null,
            'value' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }

    /**
     * Create a tariff with a range
     */
    public function withRange(string $range): static
    {
        return $this->state(fn (array $attributes) => [
            'range' => $range,
        ]);
    }

    /**
     * Create a tariff with specific width
     */
    public function withWidth(int $width): static
    {
        return $this->state(fn (array $attributes) => [
            'width' => $width,
        ]);
    }

    /**
     * Create a tariff with specific value
     */
    public function withValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $value,
        ]);
    }

    /**
     * Create a per meter tariff (with range)
     */
    public function perMeter(string $range = '0-1000', ?float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'range' => $range,
            'width' => null,
            'value' => $value ?? $this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Create a per piece tariff (with width)
     */
    public function perPiece(int $width = 200, ?float $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'range' => null,
            'width' => $width,
            'value' => $value ?? $this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Create a tariff for specific material
     */
    public function forMaterial(Material $material): static
    {
        return $this->state(fn (array $attributes) => [
            'material_id' => $material->id,
        ]);
    }

    /**
     * Create a tariff for specific user tariff
     */
    public function forUserTariff(UserTariff $userTariff): static
    {
        return $this->state(fn (array $attributes) => [
            'user_tariff_id' => $userTariff->id,
        ]);
    }
}
