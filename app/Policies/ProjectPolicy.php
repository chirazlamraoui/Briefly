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

    /** A lead sees a project when one of the teams they lead works on it. */
    public function view(User $user, Project $project): bool
    {
        return $project->teams()
            ->whereIn('teams.id', $user->managedTeamIds())
            ->exists();
    }
}
