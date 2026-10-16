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
            ->with('teamLead')
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

        $userIds = $this->withLead($memberIds, $teamLeadId);

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
        $teamLeadId = ! empty($data['team_lead_id']) ? (int) $data['team_lead_id'] : null;

        // No lead picked in the form: keep the current one while members are being edited.
        if ($teamLeadId === null && $requestedUserIds !== []) {
            $teamLeadId = $team->teamLead?->id;
        }

        $userIds = $this->withLead($requestedUserIds, $teamLeadId);

        if ($teamLeadId) {
            $this->adminUserService->assignTeamLead($team, User::query()->findOrFail($teamLeadId));
        } elseif ($team->teamLead !== null && in_array($team->teamLead->id, $userIds, true)) {
            $this->adminUserService->clearTeamLeadForTeam($team, $team->teamLead);
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
    public function assignableUsers(): Collection
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

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    private function withLead(array $userIds, ?int $teamLeadId): array
    {
        return collect($userIds)
            ->when($teamLeadId, fn ($ids) => $ids->push($teamLeadId))
            ->unique()
            ->values()
            ->all();
    }
}
