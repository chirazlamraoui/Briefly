<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUpdateTeamRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Enums\UserRole;
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
        $users = $this->adminTeamService->assignableUsersForTeam(new Team);

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

    public function edit(Team $team): View
    {
        $team = $this->adminTeamService->teamDetail($team);
        $users = $this->adminTeamService->assignableUsersForTeam($team);
        $members = $users->where('role', UserRole::Member)->values();
        $projects = $this->adminTeamService->assignableProjects();

        $selectedUserIds = $team->assignedUsers->pluck('id')->all();

        $selectedProjectIds = $team->projects->pluck('id')->all();
        $selectedTeamLeadId = $team->teamLead?->id;

        return view('admin.teams.edit', compact(
            'team',
            'users',
            'members',
            'projects',
            'selectedUserIds',
            'selectedProjectIds',
            'selectedTeamLeadId',
        ));
    }

    public function update(AdminUpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->adminTeamService->updateTeam($team, $request->validated());

        return redirect()->route('admin.teams.edit', $team)
            ->with('success', __('Team updated successfully.'));
    }
}
