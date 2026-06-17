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
    public function run(): void
    {
        $password = Hash::make('password');

        $teams = [
            ['name' => 'Web Team', 'slug' => 'web'],
            ['name' => 'Mobile Team', 'slug' => 'mobile'],
        ];

        foreach ($teams as $teamData) {
            $team = Team::create(['name' => $teamData['name']]);

            $apiBlocker = Blocker::create(['team_id' => $team->id, 'label' => 'Waiting for API access']);
            $designBlocker = Blocker::create(['team_id' => $team->id, 'label' => 'Design review pending']);
            $deployBlocker = Blocker::create(['team_id' => $team->id, 'label' => 'Deployment pipeline issue']);

            $lead = User::create([
                'name' => "{$teamData['name']} Lead",
                'email' => "lead@{$teamData['slug']}.test",
                'password' => $password,
                'role' => UserRole::TeamLead,
                'team_id' => $team->id,
            ]);

            $members = collect([
                ['Alice', 'alice'],
                ['Bob', 'bob'],
                ['Carol', 'carol'],
            ])->map(fn (array $member) => User::create([
                'name' => "{$member[0]} ({$teamData['name']})",
                'email' => "{$member[1]}@{$teamData['slug']}.test",
                'password' => $password,
                'role' => UserRole::Member,
                'team_id' => $team->id,
            ]));

            $todayBlockers = [$apiBlocker, $designBlocker, $apiBlocker];

            foreach ($members as $index => $member) {
                DailyUpdate::create([
                    'user_id' => $member->id,
                    'date' => today(),
                    'content' => [
                        'done' => "Completed task {$index} for {$teamData['name']}.",
                        'in_progress' => 'Working on feature implementation.',
                        'blocker' => '',
                    ],
                    'status' => match ($index) {
                        0 => UpdateStatus::Green,
                        1 => UpdateStatus::Orange,
                        default => UpdateStatus::Red,
                    },
                    'blocker_id' => $index === 0 ? null : $todayBlockers[$index]->id,
                ]);
            }

            for ($day = 1; $day <= 7; $day++) {
                DailyUpdate::create([
                    'user_id' => $members[1]->id,
                    'date' => today()->subDays($day),
                    'content' => [
                        'done' => 'Past progress item.',
                        'in_progress' => 'Continued work.',
                        'blocker' => '',
                    ],
                    'status' => UpdateStatus::Orange,
                    'blocker_id' => $apiBlocker->id,
                ]);

                if ($day % 2 === 0) {
                    DailyUpdate::create([
                        'user_id' => $members[2]->id,
                        'date' => today()->subDays($day),
                        'content' => [
                            'done' => 'Resolved tasks.',
                            'in_progress' => 'Next sprint item.',
                            'blocker' => '',
                        ],
                        'status' => UpdateStatus::Red,
                        'blocker_id' => $deployBlocker->id,
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
                    'blocker' => 'None.',
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
