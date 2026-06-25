<?php

namespace App\Policies;

use App\Models\Brief;
use App\Models\User;
use App\Enums\BriefStatus;
use App\Enums\UserRole;

class BriefPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Brief $brief): bool
    {
        if ($brief->team_id !== $user->team_id) {
            return false;
        }

        if ($user->role === UserRole::TeamLead) {
            return true;
        }

        return $brief->status === BriefStatus::Published;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::TeamLead;
    }

    public function update(User $user, Brief $brief): bool
    {
        return $user->role === UserRole::TeamLead
            && $brief->team_id === $user->team_id
            && $brief->status === BriefStatus::Draft;
    }

    public function publish(User $user, Brief $brief): bool
    {
        return $this->update($user, $brief);
    }
}
