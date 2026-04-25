<?php

namespace Database\Seeders;

use App\Models\PlannerField;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlannerSystemFieldsSeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private array $systemFields = [
        ['name' => 'Title',     'type' => 'text',          'position' => 0],
        ['name' => 'Status',    'type' => 'single_select', 'position' => 1],
        ['name' => 'Priority',  'type' => 'single_select', 'position' => 2],
        ['name' => 'Type',      'type' => 'single_select', 'position' => 3],
        ['name' => 'Start Date', 'type' => 'date',           'position' => 4],
        ['name' => 'End Date',  'type' => 'date',           'position' => 5],
        ['name' => 'Milestone', 'type' => 'text',           'position' => 6],
    ];

    public function run(): void
    {
        User::each(function (User $user) {
            foreach ($this->systemFields as $field) {
                PlannerField::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $field['name'], 'is_system' => true, 'milestone_id' => null],
                    array_merge($field, ['is_system' => true]),
                );
            }
        });
    }
}
