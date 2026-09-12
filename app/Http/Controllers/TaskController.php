<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskProgressRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load(['project', 'assignee', 'updates.user']);

        return view('tasks.show', [
            'task' => $task,
            'canUpdateProgress' => auth()->user()->can('updateStatus', $task),
            'canEdit' => auth()->user()->can('update', $task),
            'teamLabel' => $this->taskService->teamLabelForTask($task, auth()->user()),
        ]);
    }

    public function updateProgress(UpdateTaskProgressRequest $request, Task $task): RedirectResponse
    {
        $this->taskService->recordProgress($task, auth()->user(), $request->validated());

        return redirect()->route('tasks.show', $task)
            ->with('success', __('Task progress saved successfully.'));
    }

    public function create(Project $project): View
    {
        $this->authorize('view', $project);
        $this->authorize('create', Task::class);

        $contextTeam = $this->contextTeamForProject($project);

        abort_if($contextTeam === null, 403);

        $members = $this->taskService->assignableMembers($contextTeam);

        return view('tasks.create', compact('project', 'members', 'contextTeam'));
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Task::class);

        $contextTeam = $this->contextTeamForProject($project);

        abort_if($contextTeam === null, 403);

        $this->taskService->ensureProjectAccessibleToTeam($project, $contextTeam);

        $assignee = User::query()->findOrFail($request->validated('assigned_to'));
        $this->taskService->ensureAssigneeOnTeam($assignee, $contextTeam);

        $project->tasks()->create($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', __('Task created successfully.'));
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        $task->load(['project', 'assignee']);
        $contextTeam = $this->contextTeamForTask($task);

        abort_if($contextTeam === null, 403);

        $members = $this->taskService->assignableMembers($contextTeam);

        return view('tasks.edit', compact('task', 'members', 'contextTeam'));
    }

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $contextTeam = $this->contextTeamForTask($task);

        abort_if($contextTeam === null, 403);

        $assignee = User::query()->findOrFail($request->validated('assigned_to'));
        $this->taskService->ensureAssigneeOnTeam($assignee, $contextTeam);

        $task->update($request->validated());

        return redirect()->route('projects.show', $task->project_id)
            ->with('success', __('Task updated successfully.'));
    }

    private function contextTeamForProject(Project $project): ?Team
    {
        return Team::query()
            ->whereIn('id', auth()->user()->managedTeamIds())
            ->whereHas('projects', fn ($query) => $query->where('projects.id', $project->id))
            ->orderBy('name')
            ->first();
    }

    private function contextTeamForTask(Task $task): ?Team
    {
        return Team::query()
            ->whereIn('id', auth()->user()->managedTeamIds())
            ->whereHas('projects', fn ($query) => $query->where('projects.id', $task->project_id))
            ->orderBy('name')
            ->first();
    }
}
