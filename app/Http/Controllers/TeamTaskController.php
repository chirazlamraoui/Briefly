<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamTaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View
    {
        $team = auth()->user()->primaryTeam();

        abort_if($team === null, 403);

        $status = $request->filled('status')
            ? TaskStatus::tryFrom($request->input('status'))
            : null;

        $tasks = $this->taskService->teamTasksForLead($team, $status);

        return view('team.tasks', [
            'tasks' => $tasks,
            'status' => $status,
            'stats' => $this->taskService->teamTaskStats($team),
        ]);
    }
}
