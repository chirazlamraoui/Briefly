<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeamLead() && $user->primaryTeamId() !== null;
    }

    public function view(User $user, Project $project): bool
    {
        $teamId = $user->primaryTeamId();

        return $user->isTeamLead()
            && $teamId !== null
            && $project->teams()->where('teams.id', $teamId)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }
}
