<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeamLead() && $user->managedTeamIds() !== [];
    }

    public function view(User $user, Project $project): bool
    {
        if (! $user->isTeamLead()) {
            return false;
        }

        return $project->teams()
            ->whereIn('teams.id', $user->managedTeamIds())
            ->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }
}
