<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TaskService;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function show(User $user): View
    {
        abort_unless($user->belongsToTeam(auth()->user()->team_id), 404);
        abort_if($user->isAdmin(), 404);

        $tasks = $this->taskService->assignedTasksFor($user);
        $updates = $user->taskUpdates()->with(['task.project'])->latest()->paginate(15);

        return view('team.member', compact('user', 'tasks', 'updates'));
    }
}
