<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminTeamService
{
    public function __construct(private AdminUserService $adminUserService) {}

    /**
     * @return Collection<int, Team>
     */
    public function teams(): Collection
    {
        return Team::query()
            ->withCount([
                'assignedUsers as member_count' => fn ($query) => $query->where('role', UserRole::Member),
            ])
            ->with(['teamLead'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<int>  $memberIds
     */
    public function createTeam(string $name, ?int $teamLeadId = null, array $memberIds = []): Team
    {
        $team = Team::create(['name' => $name]);

        if ($teamLeadId) {
            $this->adminUserService->assignTeamLead($team, User::query()->findOrFail($teamLeadId));
        }

        $userIds = collect($memberIds)
            ->when($teamLeadId, fn ($ids) => $ids->push($teamLeadId))
            ->unique()
            ->values()
            ->all();

        if ($userIds !== []) {
            $this->adminUserService->syncTeamUsers($team, $userIds);
        }

        return $team;
    }

    /**
     * @param  array{
     *     name: string,
     *     team_lead_id?: int|null,
     *     user_ids?: list<int>,
     *     project_ids?: list<int>
     * }  $data
     */
    public function updateTeam(Team $team, array $data): Team
    {
        $team->update(['name' => $data['name']]);

        $requestedUserIds = $data['user_ids'] ?? [];

        $teamLeadId = isset($data['team_lead_id']) && $data['team_lead_id']
            ? (int) $data['team_lead_id']
            : null;

        if ($teamLeadId === null && $requestedUserIds !== []) {
            $teamLeadId = $team->assignedUsers()->wherePivot('is_team_lead', true)->value('users.id');
        }

        $userIds = collect($requestedUserIds)
            ->when($teamLeadId, fn ($ids) => $ids->push($teamLeadId))
            ->unique()
            ->values()
            ->all();

        if ($teamLeadId) {
            $this->adminUserService->assignTeamLead($team, User::query()->findOrFail($teamLeadId));
        } else {
            $currentLead = $team->teamLead;

            if ($currentLead !== null && in_array($currentLead->id, $userIds, true)) {
                $this->adminUserService->clearTeamLeadForTeam($team, $currentLead);
            }
        }

        $this->adminUserService->syncTeamUsers($team, $userIds);
        $team->projects()->sync($data['project_ids'] ?? []);

        return $this->teamDetail($team->fresh());
    }

    public function teamDetail(Team $team): Team
    {
        return $team->load([
            'projects' => fn ($query) => $query->orderBy('name'),
            'assignedUsers' => fn ($query) => $query->orderBy('name'),
            'teamLead',
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function assignableUsersForTeam(Team $team): Collection
    {
        return User::query()
            ->where('role', '!=', UserRole::Admin)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Project>
     */
    public function assignableProjects(): Collection
    {
        return Project::query()->orderBy('name')->get();
    }
}
