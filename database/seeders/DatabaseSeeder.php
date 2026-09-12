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
        ['Mobile Experience Refresh', 'Improve core journeys on iOS and Android.', ['nova', 'summit']],
    ];

    /** @var list<string> */
    private const LEGACY_DEMO_PROJECT_NAMES = [
        'Customer Billing Revamp',
        'Internal Tools Platform',
        'API Reliability Program',
        'Design System Refresh',
        'Quality Automation Suite',
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
        $this->cleanupRemovedDemoProjects();

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
            $this->ensureTeamLeadPivot($team, $lead);

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

            $project->teams()->sync($teamIds);

            return $project;
        });

        foreach ($teamsBySlug as $teamBundle) {
            $this->seedTeamData($teamBundle, $projects);
        }

        $this->seedMultiTeamLeadDemo($teamsBySlug, $projects);
    }

    /**
     * Demo user who leads multiple teams, is a member of another, and has personal tasks in each context.
     *
     * @param  Collection<string, array{team: Team, lead: User, members: Collection, name: string, slug: string}>  $teamsBySlug
     * @param  Collection<int, Project>  $projects
     */
    private function seedMultiTeamLeadDemo(Collection $teamsBySlug, Collection $projects): void
    {
        $marcus = User::query()->where('email', 'marcus.chen@briefly.test')->first();

        if ($marcus === null || ! $teamsBySlug->has('nova') || ! $teamsBySlug->has('atlas') || ! $teamsBySlug->has('summit')) {
            return;
        }

        $novaTeam = $teamsBySlug['nova']['team'];
        $atlasTeam = $teamsBySlug['atlas']['team'];
        $summitTeam = $teamsBySlug['summit']['team'];

        $marcus->teams()->syncWithoutDetaching([
            $summitTeam->id => ['is_team_lead' => false],
        ]);
        $this->ensureTeamLeadPivot($novaTeam, $marcus);
        $this->ensureTeamLeadPivot($atlasTeam, $marcus);

        $mobileProject = $projects->firstWhere('name', 'Mobile Experience Refresh');
        $onboardingProject = $projects->firstWhere('name', 'Employee Onboarding');
        $metricsProject = $projects->firstWhere('name', 'Metrics Dashboard');

        if ($mobileProject !== null) {
            Task::updateOrCreate(
                [
                    'project_id' => $mobileProject->id,
                    'assigned_to' => $marcus->id,
                    'title' => 'Review mobile release checklist',
                ],
                [
                    'description' => 'Final engineering review before the mobile experience refresh ships.',
                    'status' => TaskStatus::InProgress,
                    'progress_done' => 'Reviewed crash reports and performance metrics.',
                    'progress_next' => 'Sign off on release candidate build.',
                    'blocker_note' => null,
                    'completed_at' => null,
                ]
            );
        }

        if ($onboardingProject !== null) {
            Task::updateOrCreate(
                [
                    'project_id' => $onboardingProject->id,
                    'assigned_to' => $marcus->id,
                    'title' => 'Document engineering onboarding path',
                ],
                [
                    'description' => 'Contribute the Atlas Product onboarding steps for new hires.',
                    'status' => TaskStatus::Todo,
                    'progress_done' => null,
                    'progress_next' => 'Draft checklist for week-one setup.',
                    'blocker_note' => null,
                    'completed_at' => null,
                ]
            );
        }

        $atlasMembers = $teamsBySlug['atlas']['members'];
        $novaMembers = $teamsBySlug['nova']['members'];

        if ($onboardingProject !== null && $atlasMembers->isNotEmpty()) {
            $member = $atlasMembers->first();
            Task::updateOrCreate(
                [
                    'project_id' => $onboardingProject->id,
                    'assigned_to' => $member->id,
                    'title' => 'Map product onboarding milestones',
                ],
                [
                    'description' => 'Define onboarding milestones for Atlas Product new hires.',
                    'status' => TaskStatus::InProgress,
                    'progress_done' => 'Drafted milestone outline with HR.',
                    'progress_next' => 'Review with team leads.',
                    'blocker_note' => null,
                    'completed_at' => null,
                ]
            );
        }

        if ($metricsProject !== null && $atlasMembers->count() > 1) {
            $member = $atlasMembers->get(1);
            Task::updateOrCreate(
                [
                    'project_id' => $metricsProject->id,
                    'assigned_to' => $member->id,
                    'title' => 'Validate onboarding KPI widgets',
                ],
                [
                    'description' => 'Ensure onboarding KPIs appear correctly on the metrics dashboard.',
                    'status' => TaskStatus::Blocked,
                    'progress_done' => 'Built prototype widgets.',
                    'progress_next' => 'Wait for data pipeline access.',
                    'blocker_note' => 'Waiting for API access',
                    'completed_at' => null,
                ]
            );
        }

        if ($mobileProject !== null && $novaMembers->isNotEmpty()) {
            $member = $novaMembers->first();
            Task::updateOrCreate(
                [
                    'project_id' => $mobileProject->id,
                    'assigned_to' => $member->id,
                    'title' => 'Ship dark mode for mobile shell',
                ],
                [
                    'description' => 'Implement dark mode support in the mobile app shell.',
                    'status' => TaskStatus::InProgress,
                    'progress_done' => 'Completed theme token migration.',
                    'progress_next' => 'QA on iOS and Android.',
                    'blocker_note' => null,
                    'completed_at' => null,
                ]
            );
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
            $project = $teamProjects[$memberIndex % $teamProjects->count()];
            $title = self::TASK_TITLES[$memberIndex % count(self::TASK_TITLES)];
            $statusIndex = $memberIndex % 4;

            $status = match ($statusIndex) {
                0 => TaskStatus::Todo,
                1 => TaskStatus::InProgress,
                2 => TaskStatus::Blocked,
                default => TaskStatus::Done,
            };

            $progressDone = "Completed initial work on {$title}.";
            $progressNext = $status === TaskStatus::Done ? null : 'Continue implementation and testing.';
            $blockerNote = $status === TaskStatus::Blocked
                ? self::BLOCKER_NOTES[$memberIndex % count(self::BLOCKER_NOTES)]
                : null;
            $completedAt = $status === TaskStatus::Done ? now()->subDay() : null;

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

            $task->updates()->delete();

            TaskUpdate::create([
                'task_id' => $task->id,
                'user_id' => $member->id,
                'status' => TaskStatus::InProgress,
                'progress_done' => 'Started the task.',
                'progress_next' => 'Building core functionality.',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]);

            TaskUpdate::create([
                'task_id' => $task->id,
                'user_id' => $member->id,
                'status' => $status,
                'progress_done' => $progressDone,
                'progress_next' => $progressNext,
                'blocker_note' => $blockerNote,
                'created_at' => $completedAt ?? now()->subDay(),
                'updated_at' => $completedAt ?? now()->subDay(),
            ]);
        }
    }

    private function cleanupRemovedDemoProjects(): void
    {
        Project::query()
            ->whereIn('name', self::LEGACY_DEMO_PROJECT_NAMES)
            ->delete();
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

    private function ensureTeamLeadPivot(Team $team, User $lead): void
    {
        if ($lead->teams()->where('teams.id', $team->id)->exists()) {
            $lead->teams()->updateExistingPivot($team->id, ['is_team_lead' => true]);
        } else {
            $lead->teams()->attach($team->id, ['is_team_lead' => true]);
        }
    }
}
