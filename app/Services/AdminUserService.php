<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    /**
     * @return Collection<int, User>
     */
    public function assignableUsers(): Collection
    {
        return User::query()
            ->with(['team', 'teams'])
            ->where('role', '!=', UserRole::Admin)
            ->orderBy('name')
            ->get();
    }

    public function teamSummary(User $user): string
    {
        $teams = $user->teams->sortBy('name')->values();

        if ($teams->isEmpty() && $user->team) {
            return $user->team->name;
        }

        if ($teams->isEmpty()) {
            return '—';
        }

        if ($teams->count() === 1) {
            return $teams->first()->name;
        }

        return __(':count teams', ['count' => $teams->count()]);
    }

    public function projectSummary(User $user): string
    {
        $projects = $user->accessibleProjects();

        if ($projects->isEmpty()) {
            return '—';
        }

        if ($projects->count() === 1) {
            return $projects->first()->name;
        }

        return __(':count projects', ['count' => $projects->count()]);
    }

    /**
     * @param  list<int>  $teamIds
     */
    public function syncTeamsAndRole(User $user, array $teamIds, UserRole $role): void
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => __('Administrators cannot be reassigned through this form.'),
            ]);
        }

        if (! in_array($role, [UserRole::Member, UserRole::TeamLead], true)) {
            throw ValidationException::withMessages([
                'role' => __('The selected role is invalid.'),
            ]);
        }

        if ($teamIds === []) {
            throw ValidationException::withMessages([
                'team_ids' => __('Select at least one team.'),
            ]);
        }

        $user->syncTeams($teamIds);
        $this->ensurePrimaryTeamForTeamLead($user, $teamIds, $role);

        if ($role === UserRole::TeamLead) {
            User::query()
                ->where('team_id', $user->team_id)
                ->where('role', UserRole::TeamLead)
                ->where('id', '!=', $user->id)
                ->update(['role' => UserRole::Member]);
        }

        $user->update(['role' => $role]);
    }

    /**
     * @param  array{name: string, job_title?: ?string, email: string, password: string, team_ids: list<int>, role: string}  $data
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'job_title' => $data['job_title'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Member,
            'team_id' => $data['team_ids'][0],
        ]);

        $this->syncTeamsAndRole($user, $data['team_ids'], UserRole::from($data['role']));

        return $user->fresh(['team', 'teams']);
    }

    public function assignTeamLead(Team $team, User $user): void
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'team_lead_id' => __('The selected team lead is invalid.'),
            ]);
        }

        User::query()
            ->where('team_id', $team->id)
            ->where('role', UserRole::TeamLead)
            ->where('id', '!=', $user->id)
            ->update(['role' => UserRole::Member]);

        $teamIds = $user->teams()->pluck('teams.id')->push($team->id)->unique()->values()->all();
        $user->syncTeams($teamIds);
        $user->update([
            'team_id' => $team->id,
            'role' => UserRole::TeamLead,
        ]);
    }

    /**
     * @param  list<int>  $userIds
     */
    public function syncTeamUsers(Team $team, array $userIds): void
    {
        $users = User::query()
            ->whereIn('id', $userIds)
            ->where('role', '!=', UserRole::Admin)
            ->get();

        $currentUserIds = $team->assignedUsers()->pluck('users.id')->all();

        foreach ($users as $user) {
            if (! $user->teams()->where('teams.id', $team->id)->exists()) {
                $user->teams()->attach($team->id);
            }

            if ($user->team_id === null) {
                $user->update(['team_id' => $team->id]);
            }
        }

        $detachIds = array_diff($currentUserIds, $users->pluck('id')->all());

        foreach ($detachIds as $userId) {
            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $user->teams()->detach($team->id);

            if ((int) $user->team_id === (int) $team->id) {
                $nextTeamId = $user->teams()->value('teams.id');

                if ($nextTeamId === null && $user->isTeamLead()) {
                    throw ValidationException::withMessages([
                        'user_ids' => __('Team leads must remain assigned to at least one team.'),
                    ]);
                }

                $user->update(['team_id' => $nextTeamId]);
            }
        }
    }

    /**
     * @param  list<int>  $teamIds
     */
    private function ensurePrimaryTeamForTeamLead(User $user, array $teamIds, UserRole $role): void
    {
        if ($role !== UserRole::TeamLead) {
            return;
        }

        $teamIds = array_map('intval', $teamIds);

        if ($user->team_id === null || ! in_array((int) $user->team_id, $teamIds, true)) {
            $user->update(['team_id' => $teamIds[0]]);
        }
    }
}
