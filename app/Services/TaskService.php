<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        return $team->members()->orderBy('users.name')->get();
    }

    public function assignedTasksFor(User $user): Collection
    {
        return Task::query()
            ->with('project')
            ->where('assigned_to', $user->id)
            ->orderByRaw("CASE status WHEN 'BLOCKED' THEN 0 WHEN 'IN_PROGRESS' THEN 1 WHEN 'TODO' THEN 2 ELSE 3 END")
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  array{status: string, progress_done?: ?string, progress_next?: ?string, blocker_note?: ?string}  $data
     */
    public function recordProgress(Task $task, User $user, array $data): TaskUpdate
    {
        $status = TaskStatus::from($data['status']);

        $completedAt = match (true) {
            $status === TaskStatus::Done => $task->completed_at ?? now(),
            default => null,
        };

        $task->update([
            'status' => $status,
            'progress_done' => $data['progress_done'] ?? null,
            'progress_next' => $data['progress_next'] ?? null,
            'blocker_note' => $status === TaskStatus::Blocked ? ($data['blocker_note'] ?? null) : null,
            'completed_at' => $completedAt,
        ]);

        return TaskUpdate::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => $status,
            'progress_done' => $data['progress_done'] ?? null,
            'progress_next' => $data['progress_next'] ?? null,
            'blocker_note' => $status === TaskStatus::Blocked ? ($data['blocker_note'] ?? null) : null,
        ]);
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
        if (! $assignee->belongsToTeam($team->id) || ! $assignee->isMember()) {
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

    /**
     * @return array{total: int, in_progress: int, blocked: int, done: int, done_this_week: int}
     */
    public function teamTaskStats(Team $team): array
    {
        $tasks = $this->teamTasksQuery($team)->get();

        return [
            'total' => $tasks->count(),
            'in_progress' => $tasks->where('status', TaskStatus::InProgress)->count(),
            'blocked' => $tasks->where('status', TaskStatus::Blocked)->count(),
            'done' => $tasks->where('status', TaskStatus::Done)->count(),
            'done_this_week' => $tasks
                ->where('status', TaskStatus::Done)
                ->filter(fn (Task $task) => $task->completed_at?->isCurrentWeek())
                ->count(),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    public function teamTasksQuery(Team $team)
    {
        $memberIds = $team->members()->pluck('users.id');

        return Task::query()
            ->with(['project', 'assignee'])
            ->whereIn('assigned_to', $memberIds)
            ->whereHas('project.teams', fn ($query) => $query->where('teams.id', $team->id))
            ->orderByRaw("CASE status WHEN 'BLOCKED' THEN 0 WHEN 'IN_PROGRESS' THEN 1 WHEN 'TODO' THEN 2 ELSE 3 END")
            ->orderBy('title');
    }

    /**
     * @return Collection<int, Task>
     */
    public function teamTasksForProject(Team $team, Project $project): Collection
    {
        return $this->teamTasksQuery($team)
            ->where('project_id', $project->id)
            ->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function teamTasksForLead(Team $team, ?TaskStatus $status = null): Collection
    {
        $query = $this->teamTasksQuery($team);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
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
            'rate' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ];
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
        $tasks = $this->teamTasksQuery($team)->get();
        $summary = $this->completionSummary($tasks);

        $memberRates = $this->assignableMembers($team)->map(function (User $member) use ($team): array {
            $memberTasks = Task::query()
                ->where('assigned_to', $member->id)
                ->whereHas('project.teams', fn ($query) => $query->where('teams.id', $team->id))
                ->get();

            return array_merge(
                ['id' => $member->id, 'name' => $member->name],
                $this->completionSummary($memberTasks),
            );
        })->sort(function (array $a, array $b): int {
            $rateCompare = $b['rate'] <=> $a['rate'];

            return $rateCompare !== 0
                ? $rateCompare
                : strcasecmp($a['name'], $b['name']);
        })->values()->all();

        $projectRates = $this->projectsForTeam($team)->map(function (Project $project) use ($team): array {
            return array_merge(
                ['id' => $project->id, 'name' => $project->name],
                $this->completionSummary($this->teamTasksForProject($team, $project)),
            );
        })->sort(function (array $a, array $b): int {
            $rateCompare = $b['rate'] <=> $a['rate'];

            return $rateCompare !== 0
                ? $rateCompare
                : strcasecmp($a['name'], $b['name']);
        })->values()->all();

        return [
            'overall_rate' => $summary['rate'],
            'done_count' => $summary['done'],
            'total_count' => $summary['total'],
            'member_rates' => $memberRates,
            'project_rates' => $projectRates,
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
                ->map(fn (Task $task): array => [
                    'task' => $task,
                    'rate' => $task->status->progressPercent(),
                ])
                ->sort(function (array $a, array $b): int {
                    $rateCompare = $b['rate'] <=> $a['rate'];

                    return $rateCompare !== 0
                        ? $rateCompare
                        : strcasecmp($a['task']->title, $b['task']->title);
                })
                ->values(),
        ];
    }
}
