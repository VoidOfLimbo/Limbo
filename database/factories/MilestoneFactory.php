<?php

namespace Database\Factories;

use App\Enums\DeadlineType;
use App\Enums\DurationSource;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\ProgressSource;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end = fake()->dateTimeBetween('+1 month', '+6 months');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => MilestoneStatus::Active,
            'priority' => fake()->randomElement(MilestonePriority::cases()),
            'start_at' => $start,
            'end_at' => $end,
            'duration_source' => DurationSource::Derived,
            'deadline_type' => DeadlineType::Soft,
            'progress_source' => ProgressSource::Derived,
            'progress_override' => null,
            'visibility' => 'private',
            'color' => fake()->optional()->hexColor(),
        ];
    }

    public function hardDeadline(): static
    {
        return $this->state(fn () => [
            'deadline_type' => DeadlineType::Hard,
        ]);
    }

    public function softDeadline(): static
    {
        return $this->state(fn () => [
            'deadline_type' => DeadlineType::Soft,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => MilestoneStatus::Completed,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'end_at' => fake()->dateTimeBetween('-3 months', '-1 day'),
            'status' => MilestoneStatus::Active,
        ]);
    }
}
