<?php

namespace App\Services;

use App\Enums\BriefStatus;
use App\Enums\UpdateStatus;
use App\Enums\UserRole;
use App\Models\Brief;
use App\Models\DailyUpdate;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BriefService
{
    public function publish(Brief $brief): Brief
    {
        $brief->publish();

        return $brief->fresh();
    }

    public function getOrCreateTodayDraft(Team $team, User $author): Brief
    {
        return Brief::firstOrCreate(
            [
                'team_id' => $team->id,
                'date' => today(),
            ],
            [
                'content' => [
                    'done' => '',
                    'in_progress' => '',
                    'blocker' => '',
                ],
                'status' => BriefStatus::Draft,
                'created_by' => $author->id,
            ]
        );
    }

    public function getPublishedForTeamOnDate(int $teamId, Carbon $date): ?Brief
    {
        return Brief::query()
            ->where('team_id', $teamId)
            ->whereDate('date', $date)
            ->where('status', BriefStatus::Published)
            ->first();
    }

    /**
     * @return array{
     *     member_count: int,
     *     updates_submitted: int,
     *     updates_missing: int,
     *     status_counts: array<string, int>
     * }
     */
    public function teamDashboardStats(Team $team, Carbon $date): array
    {
        $members = $team->members()->orderBy('users.name')->get();
        $memberIds = $members->pluck('id');

        $updates = DailyUpdate::query()
            ->whereIn('user_id', $memberIds)
            ->whereDate('date', $date)
            ->get();

        $statusCounts = [
            UpdateStatus::Green->value => 0,
            UpdateStatus::Orange->value => 0,
            UpdateStatus::Red->value => 0,
        ];

        foreach ($updates as $update) {
            $statusCounts[$update->status->value]++;
        }

        return [
            'member_count' => $members->count(),
            'updates_submitted' => $updates->count(),
            'updates_missing' => $members->count() - $updates->count(),
            'status_counts' => $statusCounts,
        ];
    }

    /**
     * @return array{updates: Collection<int, DailyUpdate>, missing: Collection<int, User>}
     */
    public function teamUpdatesForDate(Team $team, Carbon $date): array
    {
        $members = $team->members()->orderBy('users.name')->get();
        $updates = DailyUpdate::query()
            ->with(['user', 'blocker', 'task.project'])
            ->whereIn('user_id', $members->pluck('id'))
            ->whereDate('date', $date)
            ->get()
            ->keyBy('user_id');

        $missing = $members->filter(fn (User $member) => ! $updates->has($member->id));

        return [
            'updates' => $updates,
            'missing' => $missing,
            'members' => $members,
        ];
    }

    /**
     * @return Collection<int, DailyUpdate>
     */
    public function teamUpdatesForBrief(Team $team, Carbon $date): Collection
    {
        return DailyUpdate::query()
            ->with(['user', 'blocker', 'task.project'])
            ->whereHas('user', fn ($query) => $query
                ->where('role', UserRole::Member)
                ->where(function ($inner) use ($team) {
                    $inner->where('team_id', $team->id)
                        ->orWhereHas('teams', fn ($teams) => $teams->where('teams.id', $team->id));
                }))
            ->whereDate('date', $date)
            ->orderBy('user_id')
            ->get();
    }
}
