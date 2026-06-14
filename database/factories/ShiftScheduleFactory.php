<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftSchedule>
 */
class ShiftScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'date' => now()->toDateString(),
            // Подтягиваем цех из созданной смены — keeps обратную совместимость
            // для тестов, задающих только shift_id.
            'workshop_id' => fn (array $attributes) => Shift::find($attributes['shift_id'])?->workshop_id,
        ];
    }

    /**
     * Set schedule date to a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Создать запись выходного дня для цеха (без смены).
     */
    public function dayOff(int $workshopId): static
    {
        return $this->state(fn (array $attributes) => [
            'workshop_id' => $workshopId,
            'shift_id' => null,
        ]);
    }

    /**
     * Set schedule date to tomorrow.
     */
    public function tomorrow(): static
    {
        return $this->forDate(now()->addDay()->toDateString());
    }
}
