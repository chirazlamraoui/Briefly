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

        $teamId = $user->primaryTeamId();

        return $user->isTeamLead()
            && $teamId !== null
            && $task->assignee->belongsToTeam($teamId)
            && $task->project->teams()->where('teams.id', $teamId)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isTeamLead() && $user->primaryTeamId() !== null;
    }

    public function update(User $user, Task $task): bool
    {
        $teamId = $user->primaryTeamId();

        return $user->isTeamLead()
            && $teamId !== null
            && $task->assignee->belongsToTeam($teamId)
            && $task->project->teams()->where('teams.id', $teamId)->exists();
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->assigned_to === $user->id) {
            return true;
        }

        return $this->update($user, $task);
    }
}
