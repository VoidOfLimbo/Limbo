<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Bipin Paneru',
            'email' => 'bipin.paneru.9@gmail.com',
        ]);

        $this->call(PlannerSeeder::class);
        $this->call(PlannerStressSeeder::class);
    }
}
