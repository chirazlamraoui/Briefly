<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for local use. Every account logs in with the password "password".
 * Safe to run several times: records are matched by email, name or title.
 */
class DatabaseSeeder extends Seeder
{
    /** @var list<array{0: string, 1: string, 2: string}> name, email prefix, job title */
    private const MEMBERS = [
        ['Emma Nguyen', 'emma.nguyen', 'Product Manager'],
        ['Lucas Martin', 'lucas.martin', 'UX Designer'],
        ['Olivia Brooks', 'olivia.brooks', 'Business Analyst'],
        ['Noah Williams', 'noah.williams', 'Developer'],
        ['Mia Andersen', 'mia.andersen', 'Backend Developer'],
        ['Liam Costa', 'liam.costa', 'Frontend Developer'],
        ['Ava Ibrahim', 'ava.ibrahim', 'Full-stack Developer'],
        ['Ethan Park', 'ethan.park', 'DevOps Engineer'],
        ['Chloe Dubois', 'chloe.dubois', 'Data Analyst'],
        ['Ryan Murphy', 'ryan.murphy', 'Analytics Engineer'],
        ['Isabelle Moore', 'isabelle.moore', 'BI Specialist'],
        ['Daniel Kim', 'daniel.kim', 'Data Scientist'],
        ['Sara Mendez', 'sara.mendez', 'Operations Coordinator'],
        ['Alex Turner', 'alex.turner', 'Support Engineer'],
        ['Julia Fischer', 'julia.fischer', 'Release Manager'],
        ['Thomas Berger', 'thomas.berger', 'Product Designer'],
        ['Camille Rousseau', 'camille.rousseau', 'Content Strategist'],
        ['Youssef Hassan', 'youssef.hassan', 'Infrastructure Engineer'],
        ['Priya Sharma', 'priya.sharma', 'Reporting Analyst'],
        ['Marco Rossi', 'marco.rossi', 'Software Engineer'],
        ['Hannah Okafor', 'hannah.okafor', 'QA Engineer'],
        ['Leo Schmidt', 'leo.schmidt', 'Test Automation Engineer'],
        ['Nina Alvarez', 'nina.alvarez', 'Quality Analyst'],
        ['Omar Khalil', 'omar.khalil', 'Marketing Specialist'],
    ];

    /** @var array<string, array{name: string, lead: array{0: string, 1: string, 2: string}, members: list<string>}> */
    private const TEAMS = [
        'atlas' => [
            'name' => 'Atlas Product',
            'lead' => ['Sophie Laurent', 'sophie.laurent', 'Head of Product'],
            'members' => ['emma.nguyen', 'lucas.martin', 'olivia.brooks', 'thomas.berger', 'camille.rousseau'],
        ],
        'nova' => [
            'name' => 'Nova Engineering',
            'lead' => ['Marcus Chen', 'marcus.chen', 'Engineering Manager'],
            'members' => ['mia.andersen', 'liam.costa', 'ava.ibrahim', 'ethan.park', 'marco.rossi', 'emma.nguyen'],
        ],
        'pulse' => [
            'name' => 'Pulse Analytics',
            'lead' => ['Elena Rodriguez', 'elena.rodriguez', 'Analytics Lead'],
            'members' => ['chloe.dubois', 'ryan.murphy', 'isabelle.moore', 'daniel.kim', 'priya.sharma'],
        ],
        'harbor' => [
            'name' => 'Harbor Operations',
            'lead' => ['James Okonkwo', 'james.okonkwo', 'Operations Lead'],
            'members' => ['sara.mendez', 'alex.turner', 'julia.fischer', 'youssef.hassan', 'emma.nguyen'],
        ],
        'summit' => [
            'name' => 'Summit Quality',
            'lead' => ['Nina Petrov', 'nina.petrov', 'QA Lead'],
            'members' => ['olivia.brooks', 'ethan.park', 'ryan.murphy', 'hannah.okafor', 'leo.schmidt', 'nina.alvarez'],
        ],
    ];

    /** @var list<array{0: string, 1: string, 2: list<string>}> name, description, team keys */
    private const PROJECTS = [
        ['Employee Onboarding', 'First month for new hires: accounts, equipment, and the people they should meet.', ['atlas', 'harbor']],
        ['Metrics Dashboard', 'One page of weekly figures for the leads, instead of five spreadsheets.', ['pulse', 'atlas']],
        ['Mobile Experience Refresh', 'The iOS and Android journeys people actually use: sign-in, tasks, and progress.', ['nova', 'summit']],
        ['Quarterly Planning', 'Goals and staffing for the next quarter, agreed with each team lead.', ['atlas', 'pulse']],
        ['Support Handover', 'How a finished release is handed to support, with the known issues written down.', ['harbor', 'summit']],
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

    /**
     * Notes a person would actually write, keyed by task title.
     *
     * @var array<string, array{description: string, started: string, done: string, next: string}>
     */
    private const TASK_NOTES = [
        'Implement dashboard widgets' => [
            'description' => 'The four cards :team asked for: open tasks, blocked tasks, completion, and who is overloaded.',
            'started' => 'Agreed the four cards in the Monday review.',
            'done' => 'The cards show this week’s numbers on a staging build.',
            'next' => 'Compare them with the spreadsheet finance sent on Tuesday.',
        ],
        'Fix authentication flow' => [
            'description' => 'People who reset a password are sent back to a blank form. :team wants that path to land on the home page.',
            'started' => 'Reproduced it with a reset link opened on a phone.',
            'done' => 'Reset now opens the home page when the link is still valid.',
            'next' => 'Check the expired-link message with Sophie’s test account.',
        ],
        'Write API documentation' => [
            'description' => 'Short notes for the mobile client: login, tasks, and progress. :team is tired of reading the controller.',
            'started' => 'Listed the three calls the phone actually makes.',
            'done' => 'Draft covers login and the task list, with one example each.',
            'next' => 'Add the progress call and the error when the token is missing.',
        ],
        'Prepare release notes' => [
            'description' => 'What changed in this week’s build, in language support can forward. Owned by :team.',
            'started' => 'Pulled the merged work from the last five days.',
            'done' => 'Notes are drafted. Two items still say “internal only”.',
            'next' => 'Rewrite those two lines and send the note to support.',
        ],
        'Review pull requests' => [
            'description' => 'The open reviews blocking :team’s Thursday release.',
            'started' => 'Read the two oldest reviews. One needs a test.',
            'done' => 'Left comments on both. Waiting on the test for the second.',
            'next' => 'Approve them once the test run is green.',
        ],
        'Update unit tests' => [
            'description' => 'The progress tests still expect the old status names. :team broke them in the last change.',
            'started' => 'Failed run shows six tests on the old names.',
            'done' => 'Four of the six pass with the current statuses.',
            'next' => 'Fix the two that still look at the removed column.',
        ],
        'Refactor notification service' => [
            'description' => 'The reminder mail is built in three places. :team wants one service and the same wording.',
            'started' => 'Found the three copies. The subjects already differ.',
            'done' => 'Moved the subject and the first paragraph into one class.',
            'next' => 'Point the task reminder and the blocker reminder at that class.',
        ],
        'Build reporting export' => [
            'description' => 'A CSV of tasks by status for the weekly review. :team currently copies this out by hand.',
            'started' => 'Agreed the columns: title, owner, status, project, last update.',
            'done' => 'Export runs for one project and matches the screen.',
            'next' => 'Include every project a lead can see, not only the first.',
        ],
        'Optimize database queries' => [
            'description' => 'The team page loads the same tasks once per member. :team noticed it on a slow morning.',
            'started' => 'Counted the queries on the Atlas page: far more than the number of tasks.',
            'done' => 'The member list and the task list are each loaded once.',
            'next' => 'Check the admin overview, which had the same pattern.',
        ],
        'Create onboarding flow' => [
            'description' => 'The checklist a new hire sees on day one. :team owns the product steps.',
            'started' => 'Wrote the five steps with HR. Laptop and accounts are already there.',
            'done' => 'The checklist is in the staging project, with an owner on each step.',
            'next' => 'Walk through it with the next person who joins Atlas.',
        ],
        'Set up monitoring alerts' => [
            'description' => 'An alert when the login page fails, before :team hears about it from a user.',
            'started' => 'Chose the two checks: the site is up, and login returns an error page.',
            'done' => 'The uptime check has been quiet since yesterday.',
            'next' => 'Add the login check and send it to the Harbor channel.',
        ],
        'Validate cross-browser layout' => [
            'description' => 'The task form is fine in Chrome and clipped in Safari. :team uses Safari.',
            'started' => 'Opened the form in Safari. The blocker field sits under the button.',
            'done' => 'The field is back above the button at the usual laptop width.',
            'next' => 'Repeat the check on a phone-sized window.',
        ],
    ];

    /** @var list<string> */
    private const BLOCKER_NOTES = [
        'Waiting for API access',
        'Design review pending',
        'Deployment pipeline issue',
        'Third-party service outage',
        'Waiting for QA sign-off',
    ];

    /**
     * Hand-written tasks so the multi-team lead (Marcus) and shared members have realistic data.
     *
     * @var list<array{project: string, assignee: string, title: string, description: string, status: TaskStatus, done: ?string, next: ?string, blocker: ?string}>
     */
    private const EXTRA_TASKS = [
        ['project' => 'Mobile Experience Refresh', 'assignee' => 'marcus.chen', 'title' => 'Review mobile release checklist', 'description' => 'Final engineering review before the mobile experience refresh ships.', 'status' => TaskStatus::InProgress, 'done' => 'Reviewed crash reports and performance metrics.', 'next' => 'Sign off on release candidate build.', 'blocker' => null],
        ['project' => 'Employee Onboarding', 'assignee' => 'marcus.chen', 'title' => 'Document engineering onboarding path', 'description' => 'Contribute the Atlas Product onboarding steps for new hires.', 'status' => TaskStatus::Todo, 'done' => null, 'next' => 'Draft checklist for week-one setup.', 'blocker' => null],
        ['project' => 'Employee Onboarding', 'assignee' => 'emma.nguyen', 'title' => 'Map product onboarding milestones', 'description' => 'Define onboarding milestones for Atlas Product new hires.', 'status' => TaskStatus::InProgress, 'done' => 'Drafted milestone outline with HR.', 'next' => 'Review with team leads.', 'blocker' => null],
        ['project' => 'Metrics Dashboard', 'assignee' => 'lucas.martin', 'title' => 'Validate onboarding KPI widgets', 'description' => 'Ensure onboarding KPIs appear correctly on the metrics dashboard.', 'status' => TaskStatus::Blocked, 'done' => 'Built prototype widgets.', 'next' => 'Wait for data pipeline access.', 'blocker' => 'Waiting for API access'],
        ['project' => 'Mobile Experience Refresh', 'assignee' => 'mia.andersen', 'title' => 'Ship dark mode for mobile shell', 'description' => 'Implement dark mode support in the mobile app shell.', 'status' => TaskStatus::InProgress, 'done' => 'Completed theme token migration.', 'next' => 'QA on iOS and Android.', 'blocker' => null],
        ['project' => 'Employee Onboarding', 'assignee' => 'sophie.laurent', 'title' => 'Agree the week-one agenda with HR', 'description' => 'Lock the meetings a new hire attends in the first five days.', 'status' => TaskStatus::InProgress, 'done' => 'HR sent last quarter’s agenda. Two sessions overlap.', 'next' => 'Send the revised agenda to the leads on Friday.', 'blocker' => null],
        ['project' => 'Quarterly Planning', 'assignee' => 'sophie.laurent', 'title' => 'Collect Atlas goals for next quarter', 'description' => 'Each Atlas member names one outcome they will own next quarter.', 'status' => TaskStatus::Todo, 'done' => null, 'next' => 'Ask Emma and Lucas for a first draft.', 'blocker' => null],
        ['project' => 'Metrics Dashboard', 'assignee' => 'elena.rodriguez', 'title' => 'Choose the six figures the leads will see', 'description' => 'Cut the dashboard down to the numbers discussed in Monday’s review.', 'status' => TaskStatus::InProgress, 'done' => 'Dropped three charts nobody opened last month.', 'next' => 'Confirm the remaining six with Sophie.', 'blocker' => null],
        ['project' => 'Support Handover', 'assignee' => 'james.okonkwo', 'title' => 'Write the handover note for the last release', 'description' => 'Support needs the known issues before the release goes out on Thursday.', 'status' => TaskStatus::Blocked, 'done' => 'Drafted the note from the release checklist.', 'next' => 'Add the two open bugs once Julia confirms them.', 'blocker' => 'Waiting for QA sign-off'],
        ['project' => 'Mobile Experience Refresh', 'assignee' => 'nina.petrov', 'title' => 'Sign off the task-list build', 'description' => 'Check the release candidate on a phone, not only the simulator.', 'status' => TaskStatus::Done, 'done' => 'Passed on iPhone 15. No blocker on sign-in or the task list.', 'next' => null, 'blocker' => null],
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->seedWorkspace(Hash::make('password'), true, true);
    }

    /**
     * Same company as the local demo, for a database that is already in use.
     * Existing passwords stay as they are. New accounts get $plainPasswordForNewUsers.
     */
    public function seedLive(string $plainPasswordForNewUsers): void
    {
        $this->seedWorkspace(Hash::make($plainPasswordForNewUsers), false, false);
    }

    private function seedWorkspace(string $password, bool $createLocalAdmin, bool $overwritePasswords): void
    {
        if ($createLocalAdmin) {
            User::updateOrCreate(
                ['email' => 'admin@briefly.test'],
                ['name' => 'Administrator', 'password' => $password, 'role' => UserRole::Admin],
            );
        }

        $users = collect(self::MEMBERS)->mapWithKeys(fn (array $member) => [
            $member[1] => $this->seedUser($member, UserRole::Member, $password, $overwritePasswords),
        ]);

        $teams = collect(self::TEAMS)->map(function (array $data) use ($users, $password, $overwritePasswords) {
            $team = Team::firstOrCreate(['name' => $data['name']]);

            $lead = $this->seedUser($data['lead'], UserRole::TeamLead, $password, $overwritePasswords);
            $lead->update(['team_id' => $team->id]);
            $lead->teams()->syncWithoutDetaching([$team->id => ['is_team_lead' => true]]);
            $users[$data['lead'][1]] = $lead;

            foreach ($data['members'] as $email) {
                $member = $users[$email];
                $member->teams()->syncWithoutDetaching([$team->id]);

                if ($member->team_id === null) {
                    $member->update(['team_id' => $team->id]);
                }
            }

            return $team;
        });

        foreach (self::PROJECTS as [$name, $description, $teamKeys]) {
            Project::updateOrCreate(['name' => $name], ['description' => $description])
                ->teams()
                ->sync($teams->only($teamKeys)->pluck('id')->all());
        }

        foreach (self::TEAMS as $key => $data) {
            $this->seedTeamTasks($teams[$key], collect($data['members'])->map(fn (string $email) => $users[$email]));
        }

        // Marcus leads Nova and Atlas, and is a plain member of Summit.
        $users['marcus.chen']->teams()->syncWithoutDetaching([
            $teams['summit']->id => ['is_team_lead' => false],
            $teams['atlas']->id => ['is_team_lead' => true],
        ]);

        foreach (self::EXTRA_TASKS as $task) {
            $saved = Task::updateOrCreate(
                [
                    'project_id' => Project::query()->where('name', $task['project'])->value('id'),
                    'assigned_to' => $users[$task['assignee']]->id,
                    'title' => $task['title'],
                ],
                [
                    'description' => $task['description'],
                    'status' => $task['status'],
                    'progress_done' => $task['done'],
                    'progress_next' => $task['next'],
                    'blocker_note' => $task['blocker'],
                    'completed_at' => $task['status'] === TaskStatus::Done ? now()->subDays(2) : null,
                ],
            );

            $notedAt = now()->subDay()->setTime(15, 10);
            $saved->updates()->delete();
            $saved->updates()->forceCreate([
                'user_id' => $users[$task['assignee']]->id,
                'status' => $task['status'],
                'progress_done' => $task['done'],
                'progress_next' => $task['next'],
                'blocker_note' => $task['blocker'],
                'created_at' => $notedAt,
                'updated_at' => $notedAt,
            ]);
        }
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $person  name, email prefix, job title
     */
    private function seedUser(array $person, UserRole $role, string $password, bool $overwritePasswords): User
    {
        [$name, $emailPrefix, $jobTitle] = $person;
        $email = "{$emailPrefix}@briefly.test";
        $attributes = [
            'name' => $name,
            'job_title' => $jobTitle,
            'role' => $role,
        ];

        if ($overwritePasswords || User::query()->where('email', $email)->doesntExist()) {
            $attributes['password'] = $password;
        }

        return User::updateOrCreate(['email' => $email], $attributes);
    }

    /**
     * One task per member, cycling through statuses, each with a two-step history.
     *
     * @param  Collection<int, User>  $members
     */
    private function seedTeamTasks(Team $team, Collection $members): void
    {
        $projects = $team->projects()->get();

        if ($projects->isEmpty()) {
            return;
        }

        $statuses = [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Blocked, TaskStatus::Done];

        foreach ($members->values() as $index => $member) {
            $title = self::TASK_TITLES[$index % count(self::TASK_TITLES)];
            $notes = self::TASK_NOTES[$title];
            $status = $statuses[$index % 4];
            $startedAt = now()->subDays(8 + ($index % 6))->setTime(9 + ($index % 3), 15);
            $updatedAt = $status === TaskStatus::Done
                ? now()->subDays(1 + ($index % 3))->setTime(16, 40)
                : now()->subDays(1 + ($index % 2))->setTime(11, 5);
            $progress = [
                'status' => $status,
                'progress_done' => $status === TaskStatus::Todo ? null : $notes['done'],
                'progress_next' => $status === TaskStatus::Done ? null : $notes['next'],
                'blocker_note' => $status === TaskStatus::Blocked ? self::BLOCKER_NOTES[$index % count(self::BLOCKER_NOTES)] : null,
            ];

            $task = Task::updateOrCreate(
                ['project_id' => $projects[$index % $projects->count()]->id, 'assigned_to' => $member->id, 'title' => $title],
                [
                    ...$progress,
                    'description' => str_replace(':team', $team->name, $notes['description']),
                    'completed_at' => $status === TaskStatus::Done ? $updatedAt : null,
                ],
            );

            $task->timestamps = false;
            $task->forceFill([
                'created_at' => $startedAt,
                'updated_at' => $updatedAt,
            ])->save();

            $task->updates()->delete();
            $task->updates()->forceCreate([
                'user_id' => $member->id,
                'status' => TaskStatus::Todo,
                'progress_done' => null,
                'progress_next' => $notes['started'],
                'blocker_note' => null,
                'created_at' => $startedAt,
                'updated_at' => $startedAt,
            ]);

            if ($status !== TaskStatus::Todo) {
                $task->updates()->forceCreate([
                    ...$progress,
                    'user_id' => $member->id,
                    'created_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ]);
            }
        }
    }
}
