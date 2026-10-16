<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Support\Collection;

/** Numbers for the admin overview. */
class AdminService
{
    public function __construct(private TaskService $taskService) {}

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
        $counts = Task::query()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $taskStatusCounts = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();

        return [
            'team_count' => Team::count(),
            'project_count' => Project::count(),
            'task_count' => array_sum($taskStatusCounts),
            'task_status_counts' => $taskStatusCounts,
        ];
    }

    /**
     * @return array{
     *     overall_rate: int,
     *     done_count: int,
     *     total_count: int,
     *     status_chart: array{labels: list<string>, values: list<int>, colors: list<string>},
     *     team_rates: list<array{name: string, total: int, done: int, rate: int}>,
     *     project_rates: list<array{name: string, total: int, done: int, rate: int}>
     * }
     */
    public function completionOverview(): array
    {
        $stats = $this->overviewStats();
        $totalCount = $stats['task_count'];
        $doneCount = $stats['task_status_counts'][TaskStatus::Done->value];

        $teamRates = Team::query()->get()->map(fn (Team $team) => [
            'name' => $team->name,
            ...$this->taskService->completionSummary($this->membersTasks($team)),
        ]);

        $projectRates = Project::query()->with('tasks')->get()->map(fn (Project $project) => [
            'name' => $project->name,
            ...$this->taskService->completionSummary($project->tasks),
        ]);

        return [
            'overall_rate' => $totalCount > 0 ? (int) round($doneCount / $totalCount * 100) : 0,
            'done_count' => $doneCount,
            'total_count' => $totalCount,
            'status_chart' => [
                'labels' => array_map(fn (TaskStatus $status) => $status->label(), TaskStatus::cases()),
                'values' => array_values($stats['task_status_counts']),
                'colors' => array_map(fn (TaskStatus $status) => $status->chartColor(), TaskStatus::cases()),
            ],
            'team_rates' => $this->taskService->rankByRate($teamRates),
            'project_rates' => $this->taskService->rankByRate($projectRates),
        ];
    }

    /**
     * Tasks of the team's members (not its lead) on the team's projects.
     *
     * @return Collection<int, Task>
     */
    private function membersTasks(Team $team): Collection
    {
        return Task::query()
            ->whereIn('assigned_to', $team->members()->pluck('users.id'))
            ->whereHas('project.teams', fn ($query) => $query->where('teams.id', $team->id))
            ->get();
    }

    /**
     * @return Collection<int, Project>
     */
    public function projectsOverview(): Collection
    {
        return Project::query()
            ->with('teams')
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }
}
