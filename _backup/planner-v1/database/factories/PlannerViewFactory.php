<?php

namespace Database\Factories;

use App\Models\PlannerView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannerView>
 */
class PlannerViewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'milestone_id' => null,
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['list', 'table', 'board']),
            'is_default' => false,
            'layout' => null,
            'filters' => null,
            'sorts' => null,
            'group_by' => null,
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
