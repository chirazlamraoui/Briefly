<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\AdminUpdateTeamRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Services\AdminTeamService;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTeamController extends Controller
{
    public function __construct(
        private AdminTeamService $adminTeamService,
        private AdminUserService $adminUserService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $teams = $this->adminTeamService->teams();

        return $this->respond($request, view('admin.teams.index', compact('teams')), TeamResource::collection($teams));
    }

    public function create(Request $request): View|JsonResponse
    {
        $users = $this->adminTeamService->assignableUsersForTeam(new Team);

        return $this->respond($request, view('admin.teams.create', compact('users')), [
            'users' => UserResource::collection($users)->resolve(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse|JsonResponse
    {
        $team = $this->adminTeamService->createTeam(
            $request->validated('name'),
            $request->filled('team_lead_id') ? $request->integer('team_lead_id') : null,
            $request->validated('user_ids', []),
        );
        $team = $this->adminTeamService->teamDetail($team);

        return $this->respond($request, redirect()->route('admin.teams.index')
            ->with('success', __('Team created successfully.')), new TeamResource($team), 201);
    }

    public function edit(Request $request, Team $team): View|JsonResponse
    {
        $team = $this->adminTeamService->teamDetail($team);
        $users = $this->adminTeamService->assignableUsersForTeam($team);
        $members = $users->where('role', UserRole::Member)->values();
        $projects = $this->adminTeamService->assignableProjects();

        $selectedUserIds = $team->assignedUsers->pluck('id')->all();

        $selectedProjectIds = $team->projects->pluck('id')->all();
        $selectedTeamLeadId = $team->assignedUsers
            ->first(fn ($user) => (bool) $user->pivot->is_team_lead)
            ?->id;

        return $this->respond($request, view('admin.teams.edit', compact(
            'team',
            'users',
            'projects',
            'selectedUserIds',
            'selectedProjectIds',
            'selectedTeamLeadId',
        )), [
            'team' => (new TeamResource($team))->resolve(),
            'users' => UserResource::collection($users)->resolve(),
            'members' => UserResource::collection($members)->resolve(),
            'projects' => ProjectResource::collection($projects)->resolve(),
            'selected_user_ids' => $selectedUserIds,
            'selected_project_ids' => $selectedProjectIds,
            'selected_team_lead_id' => $selectedTeamLeadId,
        ]);
    }

    public function update(AdminUpdateTeamRequest $request, Team $team): RedirectResponse|JsonResponse
    {
        $this->adminTeamService->updateTeam($team, $request->validated());
        $team = $this->adminTeamService->teamDetail($team);

        return $this->respond($request, redirect()->route('admin.teams.edit', $team)
            ->with('success', __('Team updated successfully.')), [
                'team' => (new TeamResource($team))->resolve(),
                'message' => __('Team updated successfully.'),
            ]);
    }
}
