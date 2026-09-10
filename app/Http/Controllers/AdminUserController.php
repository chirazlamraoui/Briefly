<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
        $teams = Team::query()->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'teams'));
    }

    public function edit(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $teams = Team::query()->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'teams'));
    }

    public function update(UpdateUserAssignmentRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $this->adminUserService->assignTeamAndRole(
            $user,
            $request->integer('team_id'),
            UserRole::from($request->validated('role')),
        );

        return redirect()->route('admin.users.index')
            ->with('success', __('User assignment updated successfully.'));
    }
}
