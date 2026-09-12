<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(): View
    {
        $user = auth()->user();
        $today = today();
        $personalProgress = $this->taskService->memberProgressOverview($user);

        $ledTeams = collect();

        if ($user->isTeamLead()) {
            $ledTeams = Team::query()
                ->whereIn('id', $user->managedTeamIds())
                ->orderBy('name')
                ->get()
                ->map(fn (Team $team) => [
                    'team' => $team,
                    'stats' => $this->taskService->teamTaskStats($team),
                    'progress' => $this->taskService->teamLeadProgressOverview($team),
                    'blockedTasks' => $this->taskService->teamTasksForLead($team, TaskStatus::Blocked)->take(5),
                ]);
        }

        return view('dashboard.home', compact('user', 'today', 'personalProgress', 'ledTeams'));
    }
}
