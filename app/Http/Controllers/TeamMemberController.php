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
        abort_if($user->isAdmin(), 404);

        $managedTeamIds = auth()->user()->managedTeamIds();
        $sharedTeamIds = collect($managedTeamIds)
            ->filter(fn (int $teamId) => $user->belongsToTeam($teamId))
            ->values()
            ->all();

        abort_if($sharedTeamIds === [], 404);

        $tasks = $this->taskService->assignedTasksFor($user);
        $updates = $user->taskUpdates()->with(['task.project'])->latest()->paginate(15);

        return view('team.member', compact('user', 'tasks', 'updates'));
    }
}
