<?php

namespace Database\Factories;

use App\Models\PlannerField;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannerField>
 */
class PlannerFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'milestone_id' => null,
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['text', 'number', 'date', 'single_select', 'multi_select', 'checkbox', 'url']),
            'options' => null,
            'settings' => null,
            'position' => fake()->numberBetween(0, 20),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(['is_system' => true]);
    }

    public function singleSelect(): static
    {
        return $this->state([
            'type' => 'single_select',
            'options' => [
                ['id' => fake()->uuid(), 'name' => 'Option A', 'color' => '#4ade80'],
                ['id' => fake()->uuid(), 'name' => 'Option B', 'color' => '#fb923c'],
            ],
        ]);
    }
}
