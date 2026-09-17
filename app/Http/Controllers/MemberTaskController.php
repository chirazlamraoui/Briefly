<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskUpdateResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A member's task list and progress history. */
class MemberTaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View|JsonResponse
    {
        $user = auth()->user();
        $tasks = $this->taskService->assignedTasksFor($user);

        $tasks->each(function (Task $task) use ($user): void {
            $task->setAttribute('team_label', $this->taskService->teamLabelForTask($task, $user));
        });

        // Browser → tasks/index.blade.php. Phone → TaskResource JSON.
        return $this->respond($request, view('tasks.index', [
            'tasks' => $tasks,
        ]), TaskResource::collection($tasks));
    }

    public function history(Request $request): View|JsonResponse
    {
        $updates = $this->taskService->memberProgressHistory(auth()->user());

        return $this->respond($request, view('tasks.history', compact('updates')), TaskUpdateResource::collection($updates));
    }
}
