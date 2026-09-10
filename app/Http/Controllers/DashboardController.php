<?php

namespace App\Http\Controllers;

use App\Models\Brief;
use App\Models\DailyUpdate;
use App\Services\BlockerService;
use App\Services\BriefService;
use App\Services\DailyUpdateDeadlineService;
use App\Services\TaskService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private BriefService $briefService,
        private BlockerService $blockerService,
        private DailyUpdateDeadlineService $deadlineService,
        private TaskService $taskService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $today = today();

        $todayUpdate = DailyUpdate::query()
            ->with(['blocker', 'task.project'])
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $publishedBrief = $this->briefService->getPublishedForTeamOnDate($user->team_id, $today);
        $updateReminder = $this->deadlineService->reminderFor($todayUpdate);

        if ($user->isTeamLead()) {
            $stats = $this->briefService->teamDashboardStats($user->team, $today);
            $blockerChart = $this->blockerService->teamBlockerFrequency($user->team);
            $todayDraft = Brief::query()
                ->where('team_id', $user->team_id)
                ->whereDate('date', $today)
                ->first();

            return view('dashboard.lead', compact('user', 'todayUpdate', 'publishedBrief', 'stats', 'todayDraft', 'today', 'blockerChart', 'updateReminder'));
        }

        $assignedTasks = $this->taskService->assignedTasksFor($user);

        return view('dashboard.member', compact('user', 'todayUpdate', 'publishedBrief', 'today', 'updateReminder', 'assignedTasks'));
    }
}
