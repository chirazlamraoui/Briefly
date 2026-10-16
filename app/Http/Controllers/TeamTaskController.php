<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TeamResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Team-lead view of every task across their teams. */
class TeamTaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View|JsonResponse
    {
        $user = auth()->user();
        $managedTeams = $user->managedTeams()->orderBy('name')->get();
        $status = TaskStatus::tryFrom((string) $request->input('status'));
        $taskRows = $this->taskService->tasksForManagedTeams($user, $status);
        $stats = $this->taskService->teamTaskStatsForManagedTeams($user);

        return $this->respond($request, view('team.tasks', compact('taskRows', 'status', 'stats', 'managedTeams')), [
            'stats' => $stats,
            'status' => $status?->value,
            'managed_teams' => TeamResource::collection($managedTeams)->resolve(),
            'tasks' => $taskRows->map(function (array $row) {
                $row['task']->setAttribute('team_label', $row['team']->name);

                return [
                    'task' => (new TaskResource($row['task']))->resolve(),
                    'team' => (new TeamResource($row['team']))->resolve(),
                ];
            })->all(),
        ]);
    }
}
