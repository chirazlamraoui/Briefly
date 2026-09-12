<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use Illuminate\View\View;

class MemberTaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(): View
    {
        $user = auth()->user();
        $tasks = $this->taskService->assignedTasksFor($user);

        return view('tasks.index', [
            'tasks' => $tasks,
            'grouped' => $tasks->groupBy(fn ($task) => $task->status->value),
            'taskService' => $this->taskService,
            'user' => $user,
        ]);
    }

    public function history(): View
    {
        $updates = $this->taskService->memberProgressHistory(auth()->user());

        return view('tasks.history', compact('updates'));
    }
}
