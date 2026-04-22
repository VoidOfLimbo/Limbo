<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\PlannerField;
use App\Models\PlannerFieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannerFieldValue>
 */
class PlannerFieldValueFactory extends Factory
{
    public function definition(): array
    {
        $item = Event::factory()->create();

        return [
            'field_id' => PlannerField::factory(),
            'item_id' => $item->id,
            'item_type' => Event::class,
            'value' => fake()->words(3, true),
        ];
    }
}
