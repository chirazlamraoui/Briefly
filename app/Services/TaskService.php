<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function projectsForTeam(Team $team): Collection
    {
        return $team->projects()->orderBy('name')->get();
    }

    public function assignableMembers(Team $team): Collection
    {
        return $team->members()->orderBy('name')->get();
    }

    public function assignedTasksFor(User $user): Collection
    {
        return Task::query()
            ->with('project')
            ->where('assigned_to', $user->id)
            ->orderBy('status')
            ->orderBy('title')
            ->get();
    }

    public function ensureProjectAccessibleToTeam(Project $project, Team $team): void
    {
        if (! $project->teams()->where('teams.id', $team->id)->exists()) {
            throw ValidationException::withMessages([
                'project_id' => __('This project is not available for your team.'),
            ]);
        }
    }

    public function ensureAssigneeOnTeam(User $assignee, Team $team): void
    {
        if ($assignee->team_id !== $team->id || ! $assignee->isMember()) {
            throw ValidationException::withMessages([
                'assigned_to' => __('The selected member is invalid.'),
            ]);
        }
    }

    public function ensureTaskAssignedToUser(Task $task, User $user): void
    {
        if ($task->assigned_to !== $user->id) {
            throw ValidationException::withMessages([
                'task_id' => __('The selected task is invalid.'),
            ]);
        }
    }
}
