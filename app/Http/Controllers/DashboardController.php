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
            $stats = $this->taskService->teamTaskStats($user->team);
            $blockedTasks = $this->taskService->teamTasksForLead($user->team, \App\Enums\TaskStatus::Blocked)->take(5);

            return view('dashboard.lead', compact('user', 'stats', 'blockedTasks', 'today'));
        }

        $assignedTasks = $this->taskService->assignedTasksFor($user);

        return view('dashboard.member', compact('user', 'today', 'assignedTasks'));
    }
}
