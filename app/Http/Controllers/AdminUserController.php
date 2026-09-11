<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserAssignmentRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $adminUserService) {}

    public function index(): View
    {
        $users = $this->adminUserService->assignableUsers();

        return view('admin.users.index', [
            'users' => $users,
            'adminUserService' => $this->adminUserService,
        ]);
    }

    public function create(): View
    {
        $teams = Team::query()->orderBy('name')->get();

        return view('admin.users.create', compact('teams'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->adminUserService->createUser($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', __('User created successfully.'));
    }

    public function show(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $user->load(['team', 'teams']);
        $teams = Team::query()->orderBy('name')->get();
        $selectedTeamIds = $user->teams->pluck('id')->all();
        if ($selectedTeamIds === [] && $user->team_id) {
            $selectedTeamIds = [$user->team_id];
        }

        return view('admin.users.show', compact('user', 'teams', 'selectedTeamIds'));
    }

    public function update(UpdateUserAssignmentRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $this->adminUserService->syncTeamsAndRole(
            $user,
            $request->validated('team_ids'),
            UserRole::from($request->validated('role')),
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', __('User assignment updated successfully.'));
    }
}
