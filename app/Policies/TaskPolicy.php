<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($task->assigned_to === $user->id) {
            return true;
        }

        if (! $user->isTeamLead()) {
            return false;
        }

        foreach ($user->managedTeamIds() as $teamId) {
            if ($task->assignee->belongsToTeam($teamId)
                && $task->project->teams()->where('teams.id', $teamId)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isTeamLead() && $user->managedTeamIds() !== [];
    }

    public function update(User $user, Task $task): bool
    {
        if (! $user->isTeamLead()) {
            return false;
        }

        foreach ($user->managedTeamIds() as $teamId) {
            if ($task->assignee->belongsToTeam($teamId)
                && $task->project->teams()->where('teams.id', $teamId)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $task->assigned_to === $user->id;
    }
}
