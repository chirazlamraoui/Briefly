<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use Illuminate\View\View;

class MemberTaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(): View
    {
        $tasks = $this->taskService->assignedTasksFor(auth()->user());

        return view('tasks.member-index', [
            'tasks' => $tasks,
            'grouped' => $tasks->groupBy(fn ($task) => $task->status->value),
        ]);
    }

    public function history(): View
    {
        $updates = $this->taskService->memberProgressHistory(auth()->user());

        return view('tasks.history', compact('updates'));
    }
}
