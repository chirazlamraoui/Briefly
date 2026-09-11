<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Blocker;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminTeamService
{
    public function __construct(private AdminUserService $adminUserService) {}
    /** @var list<string> */
    private const DEFAULT_BLOCKERS = [
        'Waiting for API access',
        'Design review pending',
        'Deployment pipeline issue',
        'Third-party service outage',
        'Waiting for QA sign-off',
        'Unclear product requirements',
    ];

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

        foreach (self::DEFAULT_BLOCKERS as $label) {
            Blocker::create([
                'team_id' => $team->id,
                'label' => $label,
            ]);
        }

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

        if ($teamLeadId) {
            $this->adminUserService->assignTeamLead($team, User::query()->findOrFail($teamLeadId));
        }

        return $team;
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
}
