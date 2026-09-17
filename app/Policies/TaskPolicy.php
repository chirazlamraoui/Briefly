<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($task->assigned_to === $user->id) {
            return true;
        }

        return $this->leadCanAccessTask($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->isTeamLead() && $user->managedTeamIds() !== [];
    }

    public function update(User $user, Task $task): bool
    {
        return $this->leadCanAccessTask($user, $task);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $task->assigned_to === $user->id;
    }

    private function leadCanAccessTask(User $user, Task $task): bool
    {
        if (! $user->isTeamLead()) {
            return false;
        }

        $assignee = $task->assignee;

        if ($assignee === null) {
            return false;
        }

        foreach ($user->managedTeamIds() as $teamId) {
            if ($assignee->belongsToTeam($teamId)
                && $task->project->teams()->where('teams.id', $teamId)->exists()) {
                return true;
            }
        }

        return false;
    }
}
