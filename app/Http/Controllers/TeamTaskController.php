<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamTaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $managedTeams = Team::query()
            ->whereIn('id', $user->managedTeamIds())
            ->orderBy('name')
            ->get();

        $status = $request->filled('status')
            ? TaskStatus::tryFrom($request->input('status'))
            : null;

        $taskRows = $this->taskService->tasksForManagedTeams($user, $status);
        $stats = $this->taskService->teamTaskStatsForManagedTeams($user);

        return view('team.tasks', [
            'taskRows' => $taskRows,
            'status' => $status,
            'stats' => $stats,
            'managedTeams' => $managedTeams,
        ]);
    }
}
