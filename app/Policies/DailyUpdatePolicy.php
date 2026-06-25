<?php

namespace App\Policies;

use App\Models\DailyUpdate;
use App\Models\User;

class DailyUpdatePolicy
{
    public function view(User $user, DailyUpdate $dailyUpdate): bool
    {
        if ($dailyUpdate->user_id === $user->id) {
            return true;
        }

        return $user->isTeamLead()
            && $dailyUpdate->user->team_id === $user->team_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DailyUpdate $dailyUpdate): bool
    {
        return $dailyUpdate->user_id === $user->id;
    }
}
