<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->user_id;
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->user_id;
    }
}
