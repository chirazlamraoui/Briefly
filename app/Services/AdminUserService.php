<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Team membership rules:
 * - a team has at most one lead (team_user.is_team_lead);
 * - a user's role is TEAM_LEAD as soon as they lead one team;
 * - users.team_id always points to one of the user's teams, a led one first.
 */
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
     * @param  array{name: string, job_title?: ?string, team_ids: list<int>, team_lead_ids?: list<int>}  $data
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'job_title' => $data['job_title'] ?? null,
        ]);

        $this->syncUserTeams($user, $data['team_ids'], $data['team_lead_ids'] ?? []);

        return $user->fresh(['team', 'teams']);
    }

    /**
     * Put the user in exactly these teams, leading the ones in $teamLeadIds.
     *
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

        $teamIds = array_values(array_unique(array_map('intval', $teamIds)));
        $teamLeadIds = array_map('intval', $teamLeadIds);

        $removedTeamIds = array_diff($user->teams()->pluck('teams.id')->all(), $teamIds);

        foreach ($removedTeamIds as $teamId) {
            $this->detachFromTeam($user, $teamId, 'team_ids');
        }

        foreach ($teamIds as $teamId) {
            $isLead = in_array($teamId, $teamLeadIds, true);
            $user->teams()->syncWithoutDetaching([$teamId => ['is_team_lead' => $isLead]]);

            if ($isLead) {
                $this->makeTeamLead($teamId, $user);
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

        $user->teams()->syncWithoutDetaching([$team->id => ['is_team_lead' => true]]);
        $this->makeTeamLead($team->id, $user);
        $user->update(['team_id' => $team->id]);

        $this->syncPrimaryTeamAndRole($user);
    }

    public function clearTeamLeadForTeam(Team $team, User $user): void
    {
        if (! $user->belongsToTeam($team->id)) {
            return;
        }

        $user->teams()->updateExistingPivot($team->id, ['is_team_lead' => false]);
        $this->syncPrimaryTeamAndRole($user);
    }

    /**
     * Put exactly these users in the team (admins are ignored).
     *
     * @param  list<int>  $userIds
     */
    public function syncTeamUsers(Team $team, array $userIds): void
    {
        $users = User::query()
            ->whereIn('id', $userIds)
            ->where('role', '!=', UserRole::Admin)
            ->get();

        $removedUserIds = array_diff($team->assignedUsers()->pluck('users.id')->all(), $users->pluck('id')->all());

        foreach ($users as $user) {
            if (! $user->teams()->where('teams.id', $team->id)->exists()) {
                $user->teams()->attach($team->id, ['is_team_lead' => false]);
            }

            if ($user->team_id === null) {
                $user->update(['team_id' => $team->id]);
            }
        }

        foreach (User::query()->whereIn('id', $removedUserIds)->get() as $user) {
            $this->detachFromTeam($user, $team->id, 'user_ids');
            $this->syncPrimaryTeamAndRole($user);
        }
    }

    /** A lead cannot be left without any team. */
    private function detachFromTeam(User $user, int $teamId, string $errorField): void
    {
        $wasTeamLead = $user->isTeamLeadOf($teamId);

        $user->teams()->detach($teamId);

        if ($wasTeamLead && ! $user->teams()->exists()) {
            throw ValidationException::withMessages([
                $errorField => __('Team leads must remain assigned to at least one team.'),
            ]);
        }
    }

    /** The user becomes the only lead of the team. */
    private function makeTeamLead(int $teamId, User $user): void
    {
        DB::table('team_user')->where('team_id', $teamId)->update(['is_team_lead' => false]);
        $user->teams()->updateExistingPivot($teamId, ['is_team_lead' => true]);
    }

    private function syncPrimaryTeamAndRole(User $user): void
    {
        $teamIds = $user->teams()->pluck('teams.id')->map(fn ($id) => (int) $id)->all();
        $ledTeamIds = $user->managedTeamIds();
        $currentTeamId = (int) $user->team_id;

        $teamId = match (true) {
            $ledTeamIds !== [] && ! in_array($currentTeamId, $ledTeamIds, true) => $ledTeamIds[0],
            in_array($currentTeamId, $teamIds, true) => $currentTeamId,
            default => $teamIds[0] ?? null,
        };

        $user->update([
            'role' => $ledTeamIds !== [] ? UserRole::TeamLead : UserRole::Member,
            'team_id' => $teamId,
        ]);
    }
}
