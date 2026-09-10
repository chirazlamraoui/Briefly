<?php

namespace Database\Seeders;

use App\Enums\BriefStatus;
use App\Enums\TaskStatus;
use App\Enums\UpdateStatus;
use App\Enums\UserRole;
use App\Models\Blocker;
use App\Models\Brief;
use App\Models\DailyUpdate;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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
        'Staging server unavailable',
        'Security audit pending',
    ];

    /** @var list<array{0: string, 1: string}> */
    private const MEMBERS = [
        ['Alice', 'alice'],
        ['Bob', 'bob'],
        ['Carol', 'carol'],
        ['Diana', 'diana'],
        ['Ethan', 'ethan'],
        ['Fatima', 'fatima'],
        ['George', 'george'],
        ['Hannah', 'hannah'],
    ];

    /** @var list<array{0: string, 1: string}> */
    private const TEAMS = [
        ['Web Team', 'web'],
        ['Mobile Team', 'mobile'],
        ['Backend Team', 'backend'],
        ['QA Team', 'qa'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: list<string>}>
     */
    private const PROJECTS = [
        ['Briefly Platform', 'Core daily update and brief workflow.', ['web', 'mobile', 'backend', 'qa']],
        ['Customer Portal', 'Self-service portal for end users.', ['web', 'mobile']],
        ['API Modernization', 'Migrate legacy REST endpoints to Laravel.', ['backend', 'web']],
        ['Mobile App v2', 'Next-generation iOS and Android app.', ['mobile', 'qa']],
        ['Quality Automation', 'End-to-end test suite and CI pipelines.', ['qa', 'backend']],
        ['Design System', 'Shared UI components and documentation.', ['web', 'mobile', 'qa']],
    ];

    /** @var list<string> */
    private const TASK_TITLES = [
        'Implement dashboard widgets',
        'Fix authentication flow',
        'Write API documentation',
        'Prepare release notes',
        'Review pull requests',
        'Update unit tests',
        'Refactor notification service',
        'Build reporting export',
        'Optimize database queries',
        'Create onboarding flow',
        'Set up monitoring alerts',
        'Validate cross-browser layout',
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $password = Hash::make(self::DEMO_PASSWORD);

        User::firstOrCreate(
            ['email' => 'admin@briefly.test'],
            [
                'name' => 'Administrator',
                'password' => $password,
                'role' => UserRole::Admin,
                'team_id' => null,
            ]
        );

        $teamsBySlug = collect(self::TEAMS)->mapWithKeys(function (array $teamData) use ($password) {
            [$name, $slug] = $teamData;

            $team = Team::firstOrCreate(['name' => $name]);

            $blockers = collect(self::BLOCKER_LABELS)->map(fn (string $label) => Blocker::firstOrCreate(
                ['team_id' => $team->id, 'label' => $label],
                ['team_id' => $team->id, 'label' => $label],
            ));

            $lead = User::firstOrCreate(
                ['email' => "lead@{$slug}.test"],
                [
                    'name' => "{$name} Lead",
                    'password' => $password,
                    'role' => UserRole::TeamLead,
                    'team_id' => $team->id,
                ]
            );

            if ($lead->team_id !== $team->id || $lead->role !== UserRole::TeamLead) {
                $lead->update([
                    'team_id' => $team->id,
                    'role' => UserRole::TeamLead,
                ]);
            }

            $members = collect(self::MEMBERS)->map(function (array $member) use ($name, $slug, $password, $team) {
                $user = User::firstOrCreate(
                    ['email' => "{$member[1]}@{$slug}.test"],
                    [
                        'name' => "{$member[0]} ({$name})",
                        'password' => $password,
                        'role' => UserRole::Member,
                        'team_id' => $team->id,
                    ]
                );

                if ($user->team_id !== $team->id) {
                    $user->update(['team_id' => $team->id, 'role' => UserRole::Member]);
                }

                return $user;
            });

            return [$slug => compact('team', 'blockers', 'lead', 'members', 'name', 'slug')];
        });

        $projects = collect(self::PROJECTS)->map(function (array $projectData) use ($teamsBySlug) {
            [$name, $description, $teamSlugs] = $projectData;

            $project = Project::firstOrCreate(
                ['name' => $name],
                ['description' => $description],
            );

            $teamIds = collect($teamSlugs)
                ->filter(fn (string $slug) => $teamsBySlug->has($slug))
                ->map(fn (string $slug) => $teamsBySlug[$slug]['team']->id)
                ->all();

            $project->teams()->syncWithoutDetaching($teamIds);

            return $project;
        });

        foreach ($teamsBySlug as $teamBundle) {
            $this->seedTeamData($teamBundle, $projects);
        }
    }

    /**
     * @param  array{team: Team, blockers: Collection, lead: User, members: Collection, name: string, slug: string}  $teamBundle
     * @param  Collection<int, Project>  $projects
     */
    private function seedTeamData(array $teamBundle, Collection $projects): void
    {
        $team = $teamBundle['team'];
        $blockers = $teamBundle['blockers'];
        $lead = $teamBundle['lead'];
        $members = $teamBundle['members'];
        $teamName = $teamBundle['name'];

        $teamProjects = $team->projects()->get();

        if ($teamProjects->isEmpty()) {
            return;
        }

        $memberTasks = $members->mapWithKeys(function (User $member, int $memberIndex) use ($teamProjects) {
            $tasks = collect();

            foreach (range(0, 2) as $taskOffset) {
                $project = $teamProjects[($memberIndex + $taskOffset) % $teamProjects->count()];
                $title = self::TASK_TITLES[($memberIndex + $taskOffset) % count(self::TASK_TITLES)];
                $statusIndex = ($memberIndex + $taskOffset) % 3;

                $tasks->push(Task::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'assigned_to' => $member->id,
                        'title' => $title,
                    ],
                    [
                        'description' => "Seeded task {$taskOffset} for {$member->name}.",
                        'status' => match ($statusIndex) {
                            0 => TaskStatus::Todo,
                            1 => TaskStatus::InProgress,
                            default => TaskStatus::Done,
                        },
                    ]
                ));
            }

            return [$member->id => $tasks];
        });

        $todayBlockerRotation = [
            null,
            $blockers[0]->id,
            $blockers[1]->id,
            $blockers[2]->id,
            $blockers[3]->id,
            $blockers[4]->id,
            $blockers[5]->id,
            $blockers[6]->id,
        ];

        foreach ($members as $index => $member) {
            $primaryTask = $memberTasks[$member->id]->first();

            DailyUpdate::firstOrCreate(
                [
                    'user_id' => $member->id,
                    'date' => today(),
                ],
                [
                    'task_id' => $primaryTask->id,
                    'content' => [
                        'done' => "Completed work on {$primaryTask->title} for {$teamName}.",
                        'in_progress' => 'Working on feature implementation.',
                        'blocker' => '',
                    ],
                    'status' => match ($index % 3) {
                        0 => UpdateStatus::Green,
                        1 => UpdateStatus::Orange,
                        default => UpdateStatus::Red,
                    },
                    'blocker_id' => $todayBlockerRotation[$index] ?? $blockers[$index % $blockers->count()]->id,
                ]
            );
        }

        for ($day = 1; $day <= 14; $day++) {
            foreach ($members as $memberIndex => $member) {
                if (($day + $memberIndex) % 3 !== 0) {
                    continue;
                }

                $task = $memberTasks[$member->id][($day + $memberIndex) % 3];
                $blocker = $blockers[($day + $memberIndex) % $blockers->count()];

                DailyUpdate::firstOrCreate(
                    [
                        'user_id' => $member->id,
                        'date' => today()->subDays($day),
                    ],
                    [
                        'task_id' => $task->id,
                        'content' => [
                            'done' => "Progress on {$task->title} logged on day -{$day}.",
                            'in_progress' => 'Continued sprint work.',
                            'blocker' => '',
                        ],
                        'status' => match (($day + $memberIndex) % 3) {
                            0 => UpdateStatus::Green,
                            1 => UpdateStatus::Orange,
                            default => UpdateStatus::Red,
                        },
                        'blocker_id' => ($day + $memberIndex) % 4 === 0 ? null : $blocker->id,
                    ]
                );
            }
        }

        DailyUpdate::firstOrCreate(
            [
                'user_id' => $lead->id,
                'date' => today(),
            ],
            [
                'content' => [
                    'done' => 'Reviewed team updates.',
                    'in_progress' => 'Preparing daily brief.',
                    'blocker' => '',
                ],
                'status' => UpdateStatus::Green,
                'blocker_id' => null,
            ]
        );

        Brief::firstOrCreate(
            [
                'team_id' => $team->id,
                'date' => today()->subDay(),
            ],
            [
                'content' => [
                    'done' => "Yesterday's completed work for {$teamName}.",
                    'in_progress' => 'Ongoing sprint items across multiple projects.',
                    'blocker' => 'Waiting for API access, Design review pending.',
                ],
                'status' => BriefStatus::Published,
                'created_by' => $lead->id,
                'published_at' => today()->subDay()->setTime(17, 0),
            ]
        );

        Brief::firstOrCreate(
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
                'created_by' => $lead->id,
            ]
        );
    }
}
