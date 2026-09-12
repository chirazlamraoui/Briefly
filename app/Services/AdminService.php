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
     *     overall_rate: int,
     *     done_count: int,
     *     total_count: int,
     *     status_chart: array{labels: list<string>, values: list<int>, colors: list<string>},
     *     team_rates: list<array{name: string, total: int, done: int, rate: int}>
     * }
     */
    public function completionOverview(): array
    {
        $stats = $this->overviewStats();
        $totalCount = $stats['task_count'];
        $doneCount = $stats['task_status_counts'][TaskStatus::Done->value];

        $statusChart = [
            'labels' => [],
            'values' => [],
            'colors' => [],
        ];

        $statusColors = [
            TaskStatus::Todo->value => '#a1a1aa',
            TaskStatus::InProgress->value => '#3b82f6',
            TaskStatus::Blocked->value => '#ef4444',
            TaskStatus::Done->value => '#22c55e',
        ];

        foreach (TaskStatus::cases() as $status) {
            $count = $stats['task_status_counts'][$status->value];
            $statusChart['labels'][] = $status->label();
            $statusChart['values'][] = $count;
            $statusChart['colors'][] = $statusColors[$status->value];
        }

        $teamRates = collect(Team::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team): array => array_merge(
                ['name' => $team->name],
                $this->teamTaskCompletion($team),
            )))
            ->sort(function (array $a, array $b): int {
                $rateCompare = $b['rate'] <=> $a['rate'];

                return $rateCompare !== 0
                    ? $rateCompare
                    : strcasecmp($a['name'], $b['name']);
            })
            ->values()
            ->all();

        $projectRates = collect(Project::query()
            ->orderBy('name')
            ->get()
            ->map(function (Project $project): array {
                $tasks = Task::query()->where('project_id', $project->id)->get();
                $total = $tasks->count();
                $done = $tasks->where('status', TaskStatus::Done)->count();

                return [
                    'name' => $project->name,
                    'total' => $total,
                    'done' => $done,
                    'rate' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
                ];
            }))
            ->sort(function (array $a, array $b): int {
                $rateCompare = $b['rate'] <=> $a['rate'];

                return $rateCompare !== 0
                    ? $rateCompare
                    : strcasecmp($a['name'], $b['name']);
            })
            ->values()
            ->all();

        return [
            'overall_rate' => $totalCount > 0 ? (int) round(($doneCount / $totalCount) * 100) : 0,
            'done_count' => $doneCount,
            'total_count' => $totalCount,
            'status_chart' => $statusChart,
            'team_rates' => $teamRates,
            'project_rates' => $projectRates,
        ];
    }

    /**
     * @return array{total: int, done: int, rate: int}
     */
    private function teamTaskCompletion(Team $team): array
    {
        $memberIds = $team->members()->pluck('users.id');

        if ($memberIds->isEmpty()) {
            return ['total' => 0, 'done' => 0, 'rate' => 0];
        }

        $tasks = Task::query()
            ->whereIn('assigned_to', $memberIds)
            ->whereHas('project.teams', fn ($query) => $query->where('teams.id', $team->id))
            ->get();

        $total = $tasks->count();
        $done = $tasks->where('status', TaskStatus::Done)->count();

        return [
            'total' => $total,
            'done' => $done,
            'rate' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ];
    }

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
