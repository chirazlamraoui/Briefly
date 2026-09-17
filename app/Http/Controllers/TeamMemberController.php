<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskUpdateResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Team-lead view of one member's tasks and history. */
class TeamMemberController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function show(Request $request, User $user): View|JsonResponse
    {
        abort_if($user->isAdmin(), 404);

        $managedTeamIds = auth()->user()->managedTeamIds();
        $sharedTeamIds = collect($managedTeamIds)
            ->filter(fn (int $teamId) => $user->belongsToTeam($teamId))
            ->values()
            ->all();

        abort_if($sharedTeamIds === [], 404);

        $tasks = $this->taskService->assignedTasksFor($user);
        $updates = $this->taskService->memberProgressHistory($user);
        $user->load('teams');

        return $this->respond($request, view('team.member', compact('user', 'tasks', 'updates')), [
            'user' => (new UserResource($user))->resolve(),
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'updates' => TaskUpdateResource::collection($updates)->resolve(),
            'meta' => [
                'current_page' => $updates->currentPage(),
                'last_page' => $updates->lastPage(),
                'per_page' => $updates->perPage(),
                'total' => $updates->total(),
            ],
        ]);
    }
}
