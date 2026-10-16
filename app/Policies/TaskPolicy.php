<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $task->assigned_to === $user->id || $this->leadsTaskTeam($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->isTeamLead();
    }

    public function update(User $user, Task $task): bool
    {
        return $this->leadsTaskTeam($user, $task);
    }

    /** Only the assignee writes progress. */
    public function updateStatus(User $user, Task $task): bool
    {
        return $task->assigned_to === $user->id;
    }

    /** True when the user leads a team that both works on the project and contains the assignee. */
    private function leadsTaskTeam(User $user, Task $task): bool
    {
        $teamIds = array_intersect(
            $user->managedTeamIds(),
            $task->project->teams()->pluck('teams.id')->all(),
        );

        return collect($teamIds)->contains(fn (int $teamId) => (bool) $task->assignee?->belongsToTeam($teamId));
    }
}
