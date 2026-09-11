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
        ['Emma Nguyen', 'emma.nguyen'],
        ['Lucas Martin', 'lucas.martin'],
        ['Olivia Brooks', 'olivia.brooks'],
        ['Noah Williams', 'noah.williams'],
        ['Mia Andersen', 'mia.andersen'],
        ['Liam Costa', 'liam.costa'],
        ['Ava Ibrahim', 'ava.ibrahim'],
        ['Ethan Park', 'ethan.park'],
        ['Chloe Dubois', 'chloe.dubois'],
        ['Ryan Murphy', 'ryan.murphy'],
        ['Isabelle Moore', 'isabelle.moore'],
        ['Daniel Kim', 'daniel.kim'],
        ['Sara Mendez', 'sara.mendez'],
        ['Alex Turner', 'alex.turner'],
        ['Julia Fischer', 'julia.fischer'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private const TEAMS = [
        ['Atlas Product', 'atlas', 'Sophie Laurent', 'sophie.laurent'],
        ['Nova Engineering', 'nova', 'Marcus Chen', 'marcus.chen'],
        ['Pulse Analytics', 'pulse', 'Elena Rodriguez', 'elena.rodriguez'],
        ['Harbor Operations', 'harbor', 'James Okonkwo', 'james.okonkwo'],
        ['Summit Quality', 'summit', 'Nina Petrov', 'nina.petrov'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const TEAM_MEMBER_EMAILS = [
        'atlas' => ['emma.nguyen', 'lucas.martin', 'olivia.brooks', 'noah.williams'],
        'nova' => ['mia.andersen', 'liam.costa', 'ava.ibrahim', 'ethan.park', 'emma.nguyen'],
        'pulse' => ['chloe.dubois', 'ryan.murphy', 'isabelle.moore', 'daniel.kim'],
        'harbor' => ['sara.mendez', 'alex.turner', 'julia.fischer', 'emma.nguyen'],
        'summit' => ['olivia.brooks', 'ethan.park', 'ryan.murphy', 'lucas.martin'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: list<string>}>
     */
    private const PROJECTS = [
        ['Employee Onboarding', 'Streamline the first weeks for new hires.', ['atlas', 'harbor']],
        ['Metrics Dashboard', 'Unified KPIs and reporting for leadership.', ['pulse', 'atlas']],
        ['Customer Billing Revamp', 'Modernize invoicing and payment flows.', ['nova', 'harbor']],
        ['Internal Tools Platform', 'Shared tooling for operational teams.', ['nova', 'pulse']],
        ['Mobile Experience Refresh', 'Improve core journeys on iOS and Android.', ['nova', 'summit']],
        ['API Reliability Program', 'Stabilize and document critical endpoints.', ['nova', 'pulse', 'harbor']],
        ['Design System Refresh', 'Update shared UI components and guidelines.', ['atlas', 'summit']],
        ['Quality Automation Suite', 'Expand end-to-end coverage in CI.', ['summit', 'pulse']],
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

        $usersByEmail = collect(self::MEMBERS)->mapWithKeys(function (array $member) use ($password) {
            [$name, $slug] = $member;
            $email = "{$slug}@briefly.test";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'role' => UserRole::Member,
                    'team_id' => null,
                ]
            );

            if ($user->name !== $name) {
                $user->update(['name' => $name]);
            }

            return [$slug => $user];
        });

        $teamsBySlug = collect(self::TEAMS)->mapWithKeys(function (array $teamData) use ($password, $usersByEmail) {
            [$name, $slug, $leadName, $leadSlug] = $teamData;

            $team = Team::firstOrCreate(['name' => $name]);

            $blockers = collect(self::BLOCKER_LABELS)->map(fn (string $label) => Blocker::firstOrCreate(
                ['team_id' => $team->id, 'label' => $label],
                ['team_id' => $team->id, 'label' => $label],
            ));

            $lead = User::firstOrCreate(
                ['email' => "{$leadSlug}@briefly.test"],
                [
                    'name' => $leadName,
                    'password' => $password,
                    'role' => UserRole::TeamLead,
                    'team_id' => $team->id,
                ]
            );

            $lead->update([
                'name' => $leadName,
                'team_id' => $team->id,
                'role' => UserRole::TeamLead,
            ]);
            $lead->teams()->syncWithoutDetaching([$team->id]);

            $members = collect(self::TEAM_MEMBER_EMAILS[$slug] ?? [])->map(function (string $memberSlug) use ($team, $usersByEmail) {
                $user = $usersByEmail[$memberSlug];
                $user->teams()->syncWithoutDetaching([$team->id]);

                if ($user->team_id === null) {
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

        for ($day = 1; $day <= 30; $day++) {
            foreach ($members as $memberIndex => $member) {
                if (($day + $memberIndex) % 2 !== 0) {
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

        for ($day = 1; $day <= 21; $day++) {
            $date = today()->subDays($day);

            Brief::firstOrCreate(
                [
                    'team_id' => $team->id,
                    'date' => $date,
                ],
                [
                    'content' => $this->publishedBriefContent($teamName, $day),
                    'status' => BriefStatus::Published,
                    'created_by' => $lead->id,
                    'published_at' => $date->copy()->setTime(17, 15),
                ]
            );
        }

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

    /**
     * @return array{done: string, in_progress: string, blocker: string}
     */
    private function publishedBriefContent(string $teamName, int $daysAgo): array
    {
        $themes = [
            ['Shipped dashboard improvements', 'API integration and QA fixes', 'Waiting for API access'],
            ['Closed sprint tickets', 'Mobile release candidate', 'Design review pending'],
            ['Resolved blocker on auth flow', 'Performance tuning', 'Third-party service outage'],
            ['Documentation updates merged', 'Onboarding flow polish', 'Staging server unavailable'],
            ['Test coverage increased', 'Refactoring notification service', 'Security audit pending'],
            ['Cross-team sync completed', 'Reporting export prototype', 'Unclear product requirements'],
            ['Bug fixes from regression', 'CI pipeline hardening', 'Deployment pipeline issue'],
        ];

        $theme = $themes[$daysAgo % count($themes)];

        return [
            'done' => "{$theme[0]} for {$teamName} (day -{$daysAgo}).",
            'in_progress' => "{$theme[1]} across active projects.",
            'blocker' => $theme[2].($daysAgo % 4 === 0 ? ', Waiting for QA sign-off' : '').'.',
        ];
    }
}
