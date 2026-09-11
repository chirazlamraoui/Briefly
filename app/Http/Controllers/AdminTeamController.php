<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUpdateTeamUsersRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use App\Services\AdminTeamService;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminTeamController extends Controller
{
    public function __construct(
        private AdminTeamService $adminTeamService,
        private AdminUserService $adminUserService,
    ) {}

    public function index(): View
    {
        $teams = $this->adminTeamService->teams();

        return view('admin.teams.index', compact('teams'));
    }

    public function create(): View
    {
        $users = $this->adminUserService->assignableUsers();

        return view('admin.teams.create', compact('users'));
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->adminTeamService->createTeam(
            $request->validated('name'),
            $request->filled('team_lead_id') ? $request->integer('team_lead_id') : null,
            $request->validated('user_ids', []),
        );

        return redirect()->route('admin.teams.index')
            ->with('success', __('Team created successfully.'));
    }

    public function show(Team $team): View
    {
        $team = $this->adminTeamService->teamDetail($team);
        $users = $this->adminTeamService->assignableUsersForTeam($team);
        $selectedUserIds = $team->assignedUsers->pluck('id')->all();

        return view('admin.teams.show', compact('team', 'users', 'selectedUserIds'));
    }

    public function updateUsers(AdminUpdateTeamUsersRequest $request, Team $team): RedirectResponse
    {
        $this->adminUserService->syncTeamUsers($team, $request->validated('user_ids', []));

        return redirect()->route('admin.teams.show', $team)
            ->with('success', __('Team members updated successfully.'));
    }
}
