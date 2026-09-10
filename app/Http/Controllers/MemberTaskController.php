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

        return view('tasks.member-index', compact('tasks'));
    }
}
