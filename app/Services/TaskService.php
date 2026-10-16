<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaskService
{
    /**
     * @return Collection<int, User>
     */
    public function assignableMembers(Team $team): Collection
    {
        return $team->assignedUsers()
            ->where('users.role', '!=', UserRole::Admin)
            ->orderBy('users.name')
            ->get();
    }

    /**
     * Teams led by the user that work on the project (the first one is used to create tasks).
     *
     * @return Collection<int, Team>
     */
    public function leadTeamsForProject(User $user, Project $project): Collection
    {
        return $user->managedTeams()
            ->whereHas('projects', fn ($query) => $query->where('projects.id', $project->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function assignedTasksFor(User $user): Collection
    {
        return Task::query()
            ->with(['project.teams', 'assignee'])
            ->where('assigned_to', $user->id)
            ->orderedByStatus()
            ->get();
    }

    /** Teams shown next to a task: the ones the user shares with the project, else the project's first team. */
    public function teamLabelForTask(Task $task, User $user): string
    {
        $task->loadMissing('project.teams');
        $user->loadMissing('teams');

        $projectTeams = $task->project->teams->sortBy('name');
        $sharedTeams = $projectTeams->whereIn('id', $user->teams->pluck('id'));

        $label = ($sharedTeams->isNotEmpty() ? $sharedTeams : $projectTeams->take(1))
            ->pluck('name')
            ->join(', ');

        return $label !== '' ? $label : '—';
    }

    /**
     * Save the task's current state and keep a copy in its history.
     *
     * @param  array{status: string, progress_done?: ?string, progress_next?: ?string, blocker_note?: ?string}  $data
     */
    public function recordProgress(Task $task, User $user, array $data): TaskUpdate
    {
        $status = TaskStatus::from($data['status']);

        $progress = [
            'status' => $status,
            'progress_done' => $data['progress_done'] ?? null,
            'progress_next' => $data['progress_next'] ?? null,
            'blocker_note' => $status === TaskStatus::Blocked ? ($data['blocker_note'] ?? null) : null,
        ];

        $task->update([
            ...$progress,
            'completed_at' => $status === TaskStatus::Done ? ($task->completed_at ?? now()) : null,
        ]);

        return $task->updates()->create([...$progress, 'user_id' => $user->id]);
    }

    public function ensureAssigneeOnTeam(User $assignee, Team $team): void
    {
        if (! $assignee->belongsToTeam($team->id)) {
            throw ValidationException::withMessages([
                'assigned_to' => __('The selected member is invalid.'),
            ]);
        }
    }

    /**
     * Tasks of the team's people on the team's projects.
     *
     * @return Builder<Task>
     */
    private function teamTasksQuery(Team $team): Builder
    {
        return Task::query()
            ->with(['project', 'assignee'])
            ->whereIn('assigned_to', $team->assignedUsers()->pluck('users.id'))
            ->whereHas('project.teams', fn ($query) => $query->where('teams.id', $team->id))
            ->orderedByStatus();
    }

    /**
     * @return Collection<int, Task>
     */
    public function teamTasks(Team $team, ?TaskStatus $status = null): Collection
    {
        return $this->teamTasksQuery($team)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function teamTasksForProject(Team $team, Project $project): Collection
    {
        return $this->teamTasksQuery($team)->where('project_id', $project->id)->get();
    }

    /**
     * Every task of every team the user leads, with the team it belongs to.
     *
     * @return Collection<int, array{task: Task, team: Team}>
     */
    public function tasksForManagedTeams(User $user, ?TaskStatus $status = null): Collection
    {
        return $user->managedTeams()->orderBy('name')->get()
            ->flatMap(fn (Team $team) => $this->teamTasks($team, $status)
                ->map(fn (Task $task) => ['task' => $task, 'team' => $team]))
            ->values();
    }

    /**
     * @return array{total: int, in_progress: int, blocked: int, done: int, done_this_week: int}
     */
    public function teamTaskStats(Team $team): array
    {
        return $this->taskStats($this->teamTasks($team));
    }

    /**
     * @return array{total: int, in_progress: int, blocked: int, done: int, done_this_week: int}
     */
    public function teamTaskStatsForManagedTeams(User $user): array
    {
        return $this->taskStats($this->tasksForManagedTeams($user)->pluck('task'));
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array{total: int, in_progress: int, blocked: int, done: int, done_this_week: int}
     */
    private function taskStats(Collection $tasks): array
    {
        $doneTasks = $tasks->where('status', TaskStatus::Done);

        return [
            'total' => $tasks->count(),
            'in_progress' => $tasks->where('status', TaskStatus::InProgress)->count(),
            'blocked' => $tasks->where('status', TaskStatus::Blocked)->count(),
            'done' => $doneTasks->count(),
            'done_this_week' => $doneTasks->filter(fn (Task $task) => $task->completed_at?->isCurrentWeek())->count(),
        ];
    }

    public function memberProgressHistory(User $user): LengthAwarePaginator
    {
        return TaskUpdate::query()
            ->with(['task.project'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array{total: int, done: int, rate: int}
     */
    public function completionSummary(Collection $tasks): array
    {
        $total = $tasks->count();
        $done = $tasks->where('status', TaskStatus::Done)->count();

        return [
            'total' => $total,
            'done' => $done,
            'rate' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    /**
     * Highest rate first, then alphabetical.
     *
     * @param  Collection<int, array{name: string, rate: int}>  $rows
     * @return list<array{name: string, rate: int}>
     */
    public function rankByRate(Collection $rows): array
    {
        return $rows
            ->sortBy([['rate', 'desc'], fn (array $a, array $b) => strcasecmp($a['name'], $b['name'])])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     overall_rate: int,
     *     done_count: int,
     *     total_count: int,
     *     member_rates: list<array{id: int, name: string, total: int, done: int, rate: int}>,
     *     project_rates: list<array{id: int, name: string, total: int, done: int, rate: int}>
     * }
     */
    public function teamLeadProgressOverview(Team $team): array
    {
        $tasks = $this->teamTasks($team);
        $summary = $this->completionSummary($tasks);

        $memberRates = $this->assignableMembers($team)->map(fn (User $member) => [
            'id' => $member->id,
            'name' => $member->name,
            ...$this->completionSummary($tasks->where('assigned_to', $member->id)),
        ]);

        $projectRates = $team->projects()->get()->map(fn (Project $project) => [
            'id' => $project->id,
            'name' => $project->name,
            ...$this->completionSummary($tasks->where('project_id', $project->id)),
        ]);

        return [
            'overall_rate' => $summary['rate'],
            'done_count' => $summary['done'],
            'total_count' => $summary['total'],
            'member_rates' => $this->rankByRate($memberRates),
            'project_rates' => $this->rankByRate($projectRates),
        ];
    }

    /**
     * @return array{
     *     overall_rate: int,
     *     done_count: int,
     *     total_count: int,
     *     tasks: Collection<int, array{task: Task, rate: int}>
     * }
     */
    public function memberProgressOverview(User $user): array
    {
        $tasks = $this->assignedTasksFor($user);
        $summary = $this->completionSummary($tasks);

        return [
            'overall_rate' => $summary['rate'],
            'done_count' => $summary['done'],
            'total_count' => $summary['total'],
            'tasks' => $tasks
                ->map(fn (Task $task) => ['task' => $task, 'rate' => $task->status->progressPercent()])
                ->sortBy([['rate', 'desc'], fn (array $a, array $b) => strcasecmp($a['task']->title, $b['task']->title)])
                ->values(),
        ];
    }
}
