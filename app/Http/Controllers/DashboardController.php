<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View|JsonResponse
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

        return $this->respond($request, view('dashboard.home', compact('user', 'today', 'personalProgress', 'ledTeams')), [
            'today' => $today->toDateString(),
            'personal_progress' => [
                'overall_rate' => $personalProgress['overall_rate'],
                'done_count' => $personalProgress['done_count'],
                'total_count' => $personalProgress['total_count'],
                'tasks' => $personalProgress['tasks']->map(fn (array $row) => [
                    'rate' => $row['rate'],
                    'task' => (new TaskResource($row['task']))->resolve(),
                ])->all(),
            ],
            'led_teams' => $ledTeams->map(fn (array $row) => [
                'team' => (new TeamResource($row['team']))->resolve(),
                'stats' => $row['stats'],
                'progress' => $row['progress'],
                'blocked_tasks' => TaskResource::collection($row['blockedTasks'])->resolve(),
            ])->values()->all(),
        ]);
    }
}
