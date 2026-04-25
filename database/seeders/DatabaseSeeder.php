<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'bipin.paneru.9@gmail.com'],
            User::factory()->raw([
                'name' => 'Bipin Paneru',
                'email' => 'bipin.paneru.9@gmail.com',
            ]),
        );
    }
}
