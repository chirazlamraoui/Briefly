<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Support\Collection;

class AdminService
{
    /**
     * @return array{
     *     team_count: int,
     *     project_count: int,
     *     task_count: int,
     *     task_status_counts: array<string, int>
     * }
     */
    public function overviewStats(): array
    {
        $taskStatusCounts = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status) => [$status->value => 0])
            ->all();

        foreach (Task::query()->select('status')->get() as $task) {
            if (isset($taskStatusCounts[$task->status->value])) {
                $taskStatusCounts[$task->status->value]++;
            }
        }

        return [
            'team_count' => Team::count(),
            'project_count' => Project::count(),
            'task_count' => Task::count(),
            'task_status_counts' => $taskStatusCounts,
        ];
    }

    /**
     * @return Collection<int, Team>
     */
    public function teamsOverview(): Collection
    {
        return Team::query()
            ->withCount([
                'assignedUsers as member_count' => fn ($query) => $query->where('role', UserRole::Member),
            ])
            ->with(['teamLead'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Project>
     */
    public function projectsOverview(): Collection
    {
        return Project::query()
            ->with(['teams'])
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }
}
