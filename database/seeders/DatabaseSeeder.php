<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
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
    private const BLOCKER_NOTES = [
        'Waiting for API access',
        'Design review pending',
        'Deployment pipeline issue',
        'Third-party service outage',
        'Waiting for QA sign-off',
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
        ['Thomas Berger', 'thomas.berger'],
        ['Camille Rousseau', 'camille.rousseau'],
        ['Youssef Hassan', 'youssef.hassan'],
        ['Priya Sharma', 'priya.sharma'],
        ['Marco Rossi', 'marco.rossi'],
        ['Hannah Okafor', 'hannah.okafor'],
        ['Leo Schmidt', 'leo.schmidt'],
        ['Nina Alvarez', 'nina.alvarez'],
        ['Omar Khalil', 'omar.khalil'],
    ];

    /**
     * @var array<string, string>
     */
    private const JOB_TITLES = [
        'emma.nguyen' => 'Product Manager',
        'lucas.martin' => 'UX Designer',
        'olivia.brooks' => 'Business Analyst',
        'noah.williams' => 'Developer',
        'mia.andersen' => 'Backend Developer',
        'liam.costa' => 'Frontend Developer',
        'ava.ibrahim' => 'Full-stack Developer',
        'ethan.park' => 'DevOps Engineer',
        'chloe.dubois' => 'Data Analyst',
        'ryan.murphy' => 'Analytics Engineer',
        'isabelle.moore' => 'BI Specialist',
        'daniel.kim' => 'Data Scientist',
        'sara.mendez' => 'Operations Coordinator',
        'alex.turner' => 'Support Engineer',
        'julia.fischer' => 'Release Manager',
        'thomas.berger' => 'Product Designer',
        'camille.rousseau' => 'Content Strategist',
        'youssef.hassan' => 'Infrastructure Engineer',
        'priya.sharma' => 'Reporting Analyst',
        'marco.rossi' => 'Software Engineer',
        'hannah.okafor' => 'QA Engineer',
        'leo.schmidt' => 'Test Automation Engineer',
        'nina.alvarez' => 'Quality Analyst',
        'omar.khalil' => 'Marketing Specialist',
        'sophie.laurent' => 'Head of Product',
        'marcus.chen' => 'Engineering Manager',
        'elena.rodriguez' => 'Analytics Lead',
        'james.okonkwo' => 'Operations Lead',
        'nina.petrov' => 'QA Lead',
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
        'atlas' => ['emma.nguyen', 'lucas.martin', 'olivia.brooks', 'thomas.berger', 'camille.rousseau'],
        'nova' => ['mia.andersen', 'liam.costa', 'ava.ibrahim', 'ethan.park', 'marco.rossi', 'emma.nguyen'],
        'pulse' => ['chloe.dubois', 'ryan.murphy', 'isabelle.moore', 'daniel.kim', 'priya.sharma'],
        'harbor' => ['sara.mendez', 'alex.turner', 'julia.fischer', 'youssef.hassan', 'emma.nguyen'],
        'summit' => ['olivia.brooks', 'ethan.park', 'ryan.murphy', 'hannah.okafor', 'leo.schmidt', 'nina.alvarez'],
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

        $this->cleanupLegacySeedData();

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
                    'job_title' => self::JOB_TITLES[$slug] ?? null,
                    'password' => $password,
                    'role' => UserRole::Member,
                    'team_id' => null,
                ]
            );

            if ($user->name !== $name || $user->job_title !== (self::JOB_TITLES[$slug] ?? null)) {
                $user->update([
                    'name' => $name,
                    'job_title' => self::JOB_TITLES[$slug] ?? null,
                ]);
            }

            return [$slug => $user];
        });

        $teamsBySlug = collect(self::TEAMS)->mapWithKeys(function (array $teamData) use ($password, $usersByEmail) {
            [$name, $slug, $leadName, $leadSlug] = $teamData;

            $team = Team::firstOrCreate(['name' => $name]);

            $lead = User::firstOrCreate(
                ['email' => "{$leadSlug}@briefly.test"],
                [
                    'name' => $leadName,
                    'job_title' => self::JOB_TITLES[$leadSlug] ?? null,
                    'password' => $password,
                    'role' => UserRole::TeamLead,
                    'team_id' => $team->id,
                ]
            );

            $lead->update([
                'name' => $leadName,
                'job_title' => self::JOB_TITLES[$leadSlug] ?? null,
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

            return [$slug => compact('team', 'lead', 'members', 'name', 'slug')];
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
     * @param  array{team: Team, lead: User, members: Collection, name: string, slug: string}  $teamBundle
     * @param  Collection<int, Project>  $projects
     */
    private function seedTeamData(array $teamBundle, Collection $projects): void
    {
        $team = $teamBundle['team'];
        $members = $teamBundle['members'];
        $teamName = $teamBundle['name'];

        $teamProjects = $team->projects()->get();

        if ($teamProjects->isEmpty()) {
            return;
        }

        foreach ($members as $memberIndex => $member) {
            foreach (range(0, 2) as $taskOffset) {
                $project = $teamProjects[($memberIndex + $taskOffset) % $teamProjects->count()];
                $title = self::TASK_TITLES[($memberIndex + $taskOffset) % count(self::TASK_TITLES)];
                $statusIndex = ($memberIndex + $taskOffset) % 4;

                $status = match ($statusIndex) {
                    0 => TaskStatus::Todo,
                    1 => TaskStatus::InProgress,
                    2 => TaskStatus::Blocked,
                    default => TaskStatus::Done,
                };

                $progressDone = "Completed initial work on {$title}.";
                $progressNext = $status === TaskStatus::Done ? null : 'Continue implementation and testing.';
                $blockerNote = $status === TaskStatus::Blocked
                    ? self::BLOCKER_NOTES[($memberIndex + $taskOffset) % count(self::BLOCKER_NOTES)]
                    : null;
                $completedAt = $status === TaskStatus::Done ? now()->subDays($taskOffset + 1) : null;

                $task = Task::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'assigned_to' => $member->id,
                        'title' => $title,
                    ],
                    [
                        'description' => "Work on {$title} for {$teamName}.",
                        'status' => $status,
                        'progress_done' => $progressDone,
                        'progress_next' => $progressNext,
                        'blocker_note' => $blockerNote,
                        'completed_at' => $completedAt,
                    ]
                );

                if ($task->updates()->count() < 2) {
                    TaskUpdate::create([
                        'task_id' => $task->id,
                        'user_id' => $member->id,
                        'status' => TaskStatus::InProgress,
                        'progress_done' => 'Started the task.',
                        'progress_next' => 'Building core functionality.',
                        'created_at' => now()->subDays($taskOffset + 3),
                        'updated_at' => now()->subDays($taskOffset + 3),
                    ]);

                    TaskUpdate::create([
                        'task_id' => $task->id,
                        'user_id' => $member->id,
                        'status' => $status,
                        'progress_done' => $progressDone,
                        'progress_next' => $progressNext,
                        'blocker_note' => $blockerNote,
                        'created_at' => $completedAt ?? now()->subDays($taskOffset),
                        'updated_at' => $completedAt ?? now()->subDays($taskOffset),
                    ]);
                }
            }
        }
    }

    private function cleanupLegacySeedData(): void
    {
        User::query()
            ->where('role', '!=', UserRole::Admin)
            ->where(function ($query) {
                $query->where('email', 'like', '%@web.test')
                    ->orWhere('email', 'like', '%@mobile.test')
                    ->orWhere('email', 'like', '%@backend.test')
                    ->orWhere('email', 'like', '%@qa.test')
                    ->orWhere('email', 'like', 'lead@%.test')
                    ->orWhere('name', 'like', '% (%');
            })
            ->delete();

        Team::query()->whereIn('name', [
            'Web Team',
            'Mobile Team',
            'Backend Team',
            'QA Team',
        ])->delete();

        Project::query()->whereIn('name', [
            'Briefly Platform',
            'Customer Portal',
            'API Modernization',
            'Mobile App v2',
            'Quality Automation',
            'Design System',
        ])->delete();
    }
}
