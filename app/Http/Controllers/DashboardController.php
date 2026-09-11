<?php

namespace App\Http\Controllers;

use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $today = today();

        if ($user->isTeamLead()) {
            $team = $user->primaryTeam();

            if ($team === null) {
                return redirect()->route('profile.edit')
                    ->with('error', __('Your account is not linked to a team yet. Contact an administrator.'));
            }

            $stats = $this->taskService->teamTaskStats($team);
            $blockedTasks = $this->taskService->teamTasksForLead($team, \App\Enums\TaskStatus::Blocked)->take(5);

            return view('dashboard.lead', compact('user', 'stats', 'blockedTasks', 'today'));
        }

        $assignedTasks = $this->taskService->assignedTasksFor($user);

        return view('dashboard.member', compact('user', 'today', 'assignedTasks'));
    }
}
