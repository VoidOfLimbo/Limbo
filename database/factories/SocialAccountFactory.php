<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'provider' => fake()->randomElement(['google', 'microsoft']),
            'provider_id' => fake()->numerify('##########'),
            'provider_token' => null,
            'provider_refresh_token' => null,
        ];
    }
}
