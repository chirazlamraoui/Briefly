<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Blocker;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminTeamService
{
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

    public function createTeam(string $name): Team
    {
        $team = Team::create(['name' => $name]);

        foreach (self::DEFAULT_BLOCKERS as $label) {
            Blocker::create([
                'team_id' => $team->id,
                'label' => $label,
            ]);
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
