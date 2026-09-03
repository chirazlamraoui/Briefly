<?php

namespace App\Services;

use App\Models\Blocker;
use App\Models\DailyUpdate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BlockerService
{
    /**
     * @param  array{blocker_type: string, blocker_id?: int|null, new_blocker?: string|null}  $data
     */
    public function resolveBlockerId(User $user, array $data): ?int
    {
        return match ($data['blocker_type']) {
            'none' => null,
            'existing' => $this->resolveExistingBlocker($user, (int) $data['blocker_id']),
            'new' => $this->createTeamBlocker($user, (string) $data['new_blocker'])->id,
            default => throw ValidationException::withMessages([
                'blocker_type' => __('Invalid blocker selection.'),
            ]),
        };
    }

    public function createTeamBlocker(User $user, string $label): Blocker
    {
        $label = trim($label);

        return Blocker::firstOrCreate(
            [
                'team_id' => $user->team_id,
                'label' => $label,
            ]
        );
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>, days: int}
     */
    public function teamBlockerFrequency(Team $team, int $days = 14): array
    {
        $memberIds = $team->members()->pluck('id');

        $updates = DailyUpdate::query()
            ->with('blocker')
            ->whereIn('user_id', $memberIds)
            ->whereDate('date', '>=', today()->subDays($days - 1))
            ->whereNotNull('blocker_id')
            ->get();

        $counts = [];

        foreach ($updates as $update) {
            $label = $update->blocker?->label;

            if (! $label) {
                continue;
            }

            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        arsort($counts);

        return [
            'labels' => array_keys($counts),
            'counts' => array_values($counts),
            'days' => $days,
        ];
    }

    private function resolveExistingBlocker(User $user, int $blockerId): int
    {
        $blocker = Blocker::query()
            ->where('id', $blockerId)
            ->where('team_id', $user->team_id)
            ->first();

        if (! $blocker) {
            throw ValidationException::withMessages([
                'blocker_id' => __('The selected blocker is invalid.'),
            ]);
        }

        return $blocker->id;
    }
}
