<?php

namespace Database\Seeders;

use App\Enums\BriefStatus;
use App\Enums\UpdateStatus;
use App\Enums\UserRole;
use App\Models\Blocker;
use App\Models\Brief;
use App\Models\DailyUpdate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Demo password for all seeded users (login: password). */
    private const DEMO_PASSWORD = 'password';

    /** @var list<string> */
    private const BLOCKER_LABELS = [
        'Waiting for API access',
        'Design review pending',
        'Deployment pipeline issue',
        'Third-party service outage',
        'Waiting for QA sign-off',
        'Unclear product requirements',
        'Environment configuration issue',
        'Dependency upgrade blocked',
    ];

    /** @var list<array{0: string, 1: string}> */
    private const MEMBERS = [
        ['Alice', 'alice'],
        ['Bob', 'bob'],
        ['Carol', 'carol'],
        ['Diana', 'diana'],
        ['Ethan', 'ethan'],
        ['Fatima', 'fatima'],
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $password = Hash::make(self::DEMO_PASSWORD);

        $teams = [
            ['name' => 'Web Team', 'slug' => 'web'],
            ['name' => 'Mobile Team', 'slug' => 'mobile'],
        ];

        foreach ($teams as $teamData) {
            $team = Team::create(['name' => $teamData['name']]);

            $blockers = collect(self::BLOCKER_LABELS)->map(fn (string $label) => Blocker::create([
                'team_id' => $team->id,
                'label' => $label,
            ]));

            $lead = User::create([
                'name' => "{$teamData['name']} Lead",
                'email' => "lead@{$teamData['slug']}.test",
                'password' => $password,
                'role' => UserRole::TeamLead,
                'team_id' => $team->id,
            ]);

            $members = collect(self::MEMBERS)->map(fn (array $member) => User::create([
                'name' => "{$member[0]} ({$teamData['name']})",
                'email' => "{$member[1]}@{$teamData['slug']}.test",
                'password' => $password,
                'role' => UserRole::Member,
                'team_id' => $team->id,
            ]));

            $todayBlockerRotation = [
                null,
                $blockers[0]->id,
                $blockers[1]->id,
                $blockers[2]->id,
                $blockers[3]->id,
                $blockers[4]->id,
            ];

            foreach ($members as $index => $member) {
                DailyUpdate::create([
                    'user_id' => $member->id,
                    'date' => today(),
                    'content' => [
                        'done' => "Completed task {$index} for {$teamData['name']}.",
                        'in_progress' => 'Working on feature implementation.',
                        'blocker' => '',
                    ],
                    'status' => match ($index % 3) {
                        0 => UpdateStatus::Green,
                        1 => UpdateStatus::Orange,
                        default => UpdateStatus::Red,
                    },
                    'blocker_id' => $todayBlockerRotation[$index] ?? $blockers[$index % $blockers->count()]->id,
                ]);
            }

            for ($day = 1; $day <= 14; $day++) {
                foreach ($members as $memberIndex => $member) {
                    if (($day + $memberIndex) % 3 !== 0) {
                        continue;
                    }

                    $blocker = $blockers[($day + $memberIndex) % $blockers->count()];

                    DailyUpdate::create([
                        'user_id' => $member->id,
                        'date' => today()->subDays($day),
                        'content' => [
                            'done' => "Progress logged on day -{$day}.",
                            'in_progress' => 'Continued sprint work.',
                            'blocker' => '',
                        ],
                        'status' => match (($day + $memberIndex) % 3) {
                            0 => UpdateStatus::Green,
                            1 => UpdateStatus::Orange,
                            default => UpdateStatus::Red,
                        },
                        'blocker_id' => ($day + $memberIndex) % 4 === 0 ? null : $blocker->id,
                    ]);
                }
            }

            DailyUpdate::create([
                'user_id' => $lead->id,
                'date' => today(),
                'content' => [
                    'done' => 'Reviewed team updates.',
                    'in_progress' => 'Preparing daily brief.',
                    'blocker' => '',
                ],
                'status' => UpdateStatus::Green,
                'blocker_id' => null,
            ]);

            Brief::create([
                'team_id' => $team->id,
                'date' => today()->subDay(),
                'content' => [
                    'done' => "Yesterday's completed work for {$teamData['name']}.",
                    'in_progress' => 'Ongoing sprint items.',
                    'blocker' => 'Waiting for API access, Design review pending.',
                ],
                'status' => BriefStatus::Published,
                'created_by' => $lead->id,
                'published_at' => today()->subDay()->setTime(17, 0),
            ]);

            Brief::create([
                'team_id' => $team->id,
                'date' => today(),
                'content' => [
                    'done' => '',
                    'in_progress' => '',
                    'blocker' => '',
                ],
                'status' => BriefStatus::Draft,
                'created_by' => $lead->id,
            ]);
        }
    }
}
