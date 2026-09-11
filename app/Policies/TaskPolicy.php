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

        return $user->isTeamLead()
            && $task->assignee->team_id === $user->team_id
            && $task->project->teams()->where('teams.id', $user->team_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isTeamLead();
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isTeamLead()
            && $task->assignee->team_id === $user->team_id
            && $task->project->teams()->where('teams.id', $user->team_id)->exists();
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->assigned_to === $user->id) {
            return true;
        }

        return $this->update($user, $task);
    }
}
