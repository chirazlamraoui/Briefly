<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUpdateUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $adminUserService) {}

    public function index(Request $request): View|JsonResponse
    {
        $users = $this->adminUserService->assignableUsers();

        $users->load('teams');

        return $this->respond($request, view('admin.users.index', compact('users')), UserResource::collection($users));
    }

    public function create(Request $request): View|JsonResponse
    {
        $teams = Team::query()->orderBy('name')->get();

        return $this->respond($request, view('admin.users.create', compact('teams')), [
            'teams' => TeamResource::collection($teams)->resolve(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        $user = $this->adminUserService->createUser($request->validated());
        $user->load('teams');

        return $this->respond($request, redirect()->route('admin.users.index')
            ->with('success', __('User created successfully.')), new UserResource($user), 201);
    }

    public function show(Request $request, User $user): View|JsonResponse
    {
        abort_if($user->isAdmin(), 404);

        $user->load(['team', 'teams']);
        $teams = Team::query()->orderBy('name')->get();
        $selectedTeamIds = $user->teams->pluck('id')->all();

        if ($selectedTeamIds === [] && $user->team_id) {
            $selectedTeamIds = [$user->team_id];
        }

        $selectedTeamLeadIds = $user->teams()
            ->wherePivot('is_team_lead', true)
            ->pluck('teams.id')
            ->all();

        return $this->respond($request, view('admin.users.show', compact(
            'user',
            'teams',
            'selectedTeamIds',
            'selectedTeamLeadIds',
        )), [
            'user' => (new UserResource($user))->resolve(),
            'teams' => TeamResource::collection($teams)->resolve(),
            'selected_team_ids' => $selectedTeamIds,
            'selected_team_lead_ids' => $selectedTeamLeadIds,
        ]);
    }

    public function update(AdminUpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        abort_if($user->isAdmin(), 404);

        $this->adminUserService->updateUser($user, $request->validated());
        $user->refresh()->load(['team', 'teams']);

        return $this->respond($request, redirect()->route('admin.users.show', $user)
            ->with('success', __('User updated successfully.')), [
                'user' => (new UserResource($user))->resolve(),
                'message' => __('User updated successfully.'),
            ]);
    }
}
