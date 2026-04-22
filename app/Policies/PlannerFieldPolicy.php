<?php

namespace App\Policies;

use App\Models\PlannerField;
use App\Models\User;

class PlannerFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PlannerField $plannerField): bool
    {
        return $user->id === $plannerField->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PlannerField $plannerField): bool
    {
        return $user->id === $plannerField->user_id && ! $plannerField->is_system;
    }

    public function delete(User $user, PlannerField $plannerField): bool
    {
        return $user->id === $plannerField->user_id && ! $plannerField->is_system;
    }
}
