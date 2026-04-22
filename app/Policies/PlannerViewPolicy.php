<?php

namespace App\Policies;

use App\Models\PlannerView;
use App\Models\User;

class PlannerViewPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PlannerView $plannerView): bool
    {
        return $user->id === $plannerView->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PlannerView $plannerView): bool
    {
        return $user->id === $plannerView->user_id;
    }

    public function delete(User $user, PlannerView $plannerView): bool
    {
        return $user->id === $plannerView->user_id;
    }
}
