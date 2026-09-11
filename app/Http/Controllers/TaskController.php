<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\Task;
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

        $task->load(['project', 'assignee']);

        return view('tasks.show', [
            'task' => $task,
            'canUpdateStatus' => auth()->user()->can('updateStatus', $task),
            'canEdit' => auth()->user()->can('update', $task),
        ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()->route('tasks.show', $task)
            ->with('success', __('Task status updated successfully.'));
    }

    public function create(Project $project): View
    {
        $this->authorize('view', $project);
        $this->authorize('create', Task::class);

        $team = auth()->user()->team;
        $members = $this->taskService->assignableMembers($team);

        return view('tasks.create', compact('project', 'members'));
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', Task::class);

        $team = auth()->user()->team;
        $this->taskService->ensureProjectAccessibleToTeam($project, $team);

        $assignee = User::query()->findOrFail($request->validated('assigned_to'));
        $this->taskService->ensureAssigneeOnTeam($assignee, $team);

        $project->tasks()->create($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', __('Task created successfully.'));
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        $task->load(['project', 'assignee']);
        $members = $this->taskService->assignableMembers(auth()->user()->team);

        return view('tasks.edit', compact('task', 'members'));
    }

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $team = auth()->user()->team;
        $assignee = User::query()->findOrFail($request->validated('assigned_to'));
        $this->taskService->ensureAssigneeOnTeam($assignee, $team);

        $task->update($request->validated());

        return redirect()->route('projects.show', $task->project_id)
            ->with('success', __('Task updated successfully.'));
    }
}
