<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskProgressRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Open a task, update progress, or (team lead) create/edit a task. */
class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function show(Request $request, Task $task): View|JsonResponse
    {
        $this->authorize('view', $task);

        $user = auth()->user();
        $task->load(['project', 'assignee', 'updates.user']);
        $teamLabel = $this->taskService->teamLabelForTask($task, $user);
        $task->setAttribute('team_label', $teamLabel);
        $canUpdateProgress = $user->can('updateStatus', $task);
        $canEdit = $user->can('update', $task);

        return $this->respond($request, view('tasks.show', compact('task', 'canUpdateProgress', 'canEdit', 'teamLabel')), [
            'task' => (new TaskResource($task))->resolve(),
            'can_update_progress' => $canUpdateProgress,
            'can_edit' => $canEdit,
            'team_label' => $teamLabel,
        ]);
    }

    public function updateProgress(UpdateTaskProgressRequest $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->taskService->recordProgress($task, auth()->user(), $request->validated());
        $task->refresh()->load(['project', 'assignee', 'updates.user']);
        $task->setAttribute('team_label', $this->taskService->teamLabelForTask($task, auth()->user()));

        return $this->respond($request, redirect()->route('tasks.show', $task)
            ->with('success', __('Task progress saved successfully.')), [
                'task' => (new TaskResource($task))->resolve(),
                'message' => __('Task progress saved successfully.'),
            ]);
    }

    public function create(Request $request, Project $project): View|JsonResponse
    {
        $this->authorize('create', Task::class);
        $contextTeam = $this->contextTeam($project);
        $members = $this->taskService->assignableMembers($contextTeam);

        return $this->respond($request, view('tasks.create', compact('project', 'members', 'contextTeam')), [
            'project' => $project->only(['id', 'name', 'description']),
            'members' => UserResource::collection($members)->resolve(),
            'context_team' => (new TeamResource($contextTeam))->resolve(),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Task::class);
        $contextTeam = $this->contextTeam($project);
        $this->ensureAssigneeOnTeam($request, $contextTeam);

        $task = $project->tasks()->create($request->validated());
        $task->load(['project', 'assignee']);

        return $this->respond($request, redirect()->route('projects.show', $project)
            ->with('success', __('Task created successfully.')), new TaskResource($task), 201);
    }

    public function edit(Request $request, Task $task): View|JsonResponse
    {
        $this->authorize('update', $task);
        $task->load(['project', 'assignee']);
        $contextTeam = $this->contextTeam($task->project);
        $members = $this->taskService->assignableMembers($contextTeam);

        return $this->respond($request, view('tasks.edit', compact('task', 'members', 'contextTeam')), [
            'task' => (new TaskResource($task))->resolve(),
            'members' => UserResource::collection($members)->resolve(),
            'context_team' => (new TeamResource($contextTeam))->resolve(),
        ]);
    }

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);
        $this->ensureAssigneeOnTeam($request, $this->contextTeam($task->project));

        $task->update($request->validated());
        $task->refresh()->load(['project', 'assignee']);

        return $this->respond($request, redirect()->route('projects.show', $task->project_id)
            ->with('success', __('Task updated successfully.')), [
                'task' => (new TaskResource($task))->resolve(),
                'message' => __('Task updated successfully.'),
            ]);
    }

    /** The lead's team on this project. 403 when they lead none of its teams. */
    private function contextTeam(Project $project): Team
    {
        return $this->taskService->leadTeamsForProject(auth()->user(), $project)->first() ?? abort(403);
    }

    private function ensureAssigneeOnTeam(StoreTaskRequest $request, Team $team): void
    {
        $assignee = User::query()->findOrFail($request->validated('assigned_to'));
        $this->taskService->ensureAssigneeOnTeam($assignee, $team);
    }
}
