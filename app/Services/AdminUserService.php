<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * @param  array{
     *     name: string,
     *     job_title?: ?string,
     *     team_ids: list<int>,
     *     team_lead_ids?: list<int>
     * }  $data
     */
    public function updateUser(User $user, array $data): User
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => __('Administrators cannot be reassigned through this form.'),
            ]);
        }

        $user->update([
            'name' => $data['name'],
            'job_title' => $data['job_title'] ?? null,
        ]);

        $this->syncUserTeams(
            $user,
            $data['team_ids'],
            $data['team_lead_ids'] ?? [],
        );

        return $user->fresh(['team', 'teams']);
    }

    /**
     * @param  array{name: string, job_title?: ?string, email: string, password: string, team_ids: list<int>, team_lead_ids?: list<int>}  $data
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

        $this->syncUserTeams($user, $data['team_ids'], $data['team_lead_ids'] ?? []);

        return $user->fresh(['team', 'teams']);
    }

    /**
     * @param  list<int>  $teamIds
     * @param  list<int>  $teamLeadIds
     */
    public function syncUserTeams(User $user, array $teamIds, array $teamLeadIds): void
    {
        if ($teamIds === []) {
            throw ValidationException::withMessages([
                'team_ids' => __('Select at least one team.'),
            ]);
        }

        $teamIds = collect($teamIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $teamLeadIds = collect($teamLeadIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($teamIds)
            ->unique()
            ->values()
            ->all();

        $currentTeamIds = $user->teams()->pluck('teams.id')->all();
        $detachIds = array_diff($currentTeamIds, $teamIds);

        foreach ($detachIds as $teamId) {
            $wasTeamLead = $user->isTeamLeadOf($teamId);

            $user->teams()->detach($teamId);

            if ((int) $user->team_id === (int) $teamId) {
                $nextTeamId = $user->teams()->value('teams.id');

                if ($nextTeamId === null && $wasTeamLead) {
                    throw ValidationException::withMessages([
                        'team_ids' => __('Team leads must remain assigned to at least one team.'),
                    ]);
                }

                $user->update(['team_id' => $nextTeamId]);
            }
        }

        foreach ($teamIds as $teamId) {
            $isLead = in_array($teamId, $teamLeadIds, true);

            if (! $user->teams()->where('teams.id', $teamId)->exists()) {
                $user->teams()->attach($teamId, ['is_team_lead' => $isLead]);
            } else {
                $user->teams()->updateExistingPivot($teamId, ['is_team_lead' => $isLead]);
            }

            if ($isLead) {
                $this->clearTeamLeadFlags($teamId);
                $this->setTeamLeadFlag($teamId, $user->id, true);
            }
        }

        $this->syncPrimaryTeamAndRole($user);
    }

    public function assignTeamLead(Team $team, User $user): void
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'team_lead_id' => __('The selected team lead is invalid.'),
            ]);
        }

        if (! $user->teams()->where('teams.id', $team->id)->exists()) {
            $user->teams()->attach($team->id, ['is_team_lead' => true]);
        }

        $this->clearTeamLeadFlags($team->id);
        $this->setTeamLeadFlag($team->id, $user->id, true);
        $user->update(['team_id' => $team->id]);

        $this->syncPrimaryTeamAndRole($user);
    }

    public function clearTeamLeadForTeam(Team $team, User $user): void
    {
        if (! $team->assignedUsers()->where('users.id', $user->id)->exists()) {
            return;
        }

        $this->setTeamLeadFlag($team->id, $user->id, false);
        $this->syncPrimaryTeamAndRole($user);
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
                $user->teams()->attach($team->id, ['is_team_lead' => false]);
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

            $wasTeamLead = $user->isTeamLeadOf($team);

            $user->teams()->detach($team->id);

            if ((int) $user->team_id === (int) $team->id) {
                $nextTeamId = $user->teams()->value('teams.id');

                if ($nextTeamId === null && $wasTeamLead) {
                    throw ValidationException::withMessages([
                        'user_ids' => __('Team leads must remain assigned to at least one team.'),
                    ]);
                }

                $user->update(['team_id' => $nextTeamId]);
            }

            if ($wasTeamLead) {
                $this->syncPrimaryTeamAndRole($user);
            }
        }
    }

    private function syncPrimaryTeamAndRole(User $user): void
    {
        $isTeamLead = $user->teams()->wherePivot('is_team_lead', true)->exists();
        $ledTeamId = $user->teams()->wherePivot('is_team_lead', true)->value('teams.id');
        $firstTeamId = $user->teams()->value('teams.id');

        $user->update([
            'role' => $isTeamLead ? UserRole::TeamLead : UserRole::Member,
        ]);

        if ($ledTeamId !== null && ! $user->isTeamLeadOf((int) $user->team_id)) {
            $user->update(['team_id' => $ledTeamId]);

            return;
        }

        if (! $user->teams()->where('teams.id', $user->team_id)->exists()) {
            $user->update(['team_id' => $firstTeamId]);
        }
    }

    private function clearTeamLeadFlags(int $teamId): void
    {
        DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('is_team_lead', true)
            ->update(['is_team_lead' => false]);
    }

    private function setTeamLeadFlag(int $teamId, int $userId, bool $isTeamLead = true): void
    {
        $updated = DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->update([
                'is_team_lead' => $isTeamLead,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            DB::table('team_user')->insert([
                'team_id' => $teamId,
                'user_id' => $userId,
                'is_team_lead' => $isTeamLead,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
