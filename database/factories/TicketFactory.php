<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Состояние по умолчанию: новый тикет от случайного сотрудника.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => 'Не работает функционал: '.$this->faker->sentence(),
            'page_url' => $this->faker->optional()->url(),
            'screenshot' => null,
            'status' => Ticket::STATUS_NEW,
            'closed_at' => null,
        ];
    }

    /** Тикет в работе. */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);
    }

    /** Закрытый тикет. */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    /** Тикет в корзине. */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Ticket::STATUS_DELETED,
        ]);
    }
}
