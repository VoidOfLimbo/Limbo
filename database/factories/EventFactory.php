<?php

namespace Database\Factories;

use App\Enums\EventPriority;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'milestone_id' => null,
            'parent_event_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => EventType::Event,
            'status' => EventStatus::Upcoming,
            'priority' => fake()->randomElement(EventPriority::cases()),
            'start_at' => fake()->dateTimeBetween('now', '+3 months'),
            'end_at' => fake()->dateTimeBetween('+1 day', '+4 months'),
            'is_all_day' => false,
            'is_milestone_marker' => false,
            'recurrence_rule' => null,
            'recurrence_ends_at' => null,
            'recurrence_count' => null,
            'visibility' => EventVisibility::Private,
            'color' => fake()->optional()->hexColor(),
            'location' => fake()->optional()->city(),
            'snoozed_until' => null,
            'snooze_count' => 0,
        ];
    }

    public function snoozed(): static
    {
        return $this->state(fn () => [
            'snoozed_until' => now()->addHours(2),
            'snooze_count' => fake()->numberBetween(1, 5),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Completed,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::InProgress,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Draft,
        ]);
    }

    public function backlog(): static
    {
        return $this->state(fn () => [
            'milestone_id' => null,
        ]);
    }

    public function task(): static
    {
        return $this->state(fn () => [
            'type' => EventType::Task,
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'is_all_day' => true,
            'start_at' => fake()->dateTimeBetween('now', '+3 months'),
            'end_at' => null,
        ]);
    }
}
