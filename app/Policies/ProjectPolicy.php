<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeamLead();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isTeamLead()
            && $project->teams()->where('teams.id', $user->team_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isTeamLead();
    }
}
