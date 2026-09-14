<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_login_returns_token_and_user(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create([
            'team_id' => $team->id,
            'email' => 'member@test.com',
        ]);

        $this->postJson(route('login'), [
            'email' => 'member@test.com',
            'password' => 'password',
            'device_name' => 'test',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'member@test.com')
            ->assertJsonPath('user.role', 'MEMBER')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'teams']]);

        $this->assertNotEmpty($user->tokens()->first());
    }

    public function test_json_login_rejects_invalid_credentials_with_422(): void
    {
        $team = Team::factory()->create();
        User::factory()->create([
            'team_id' => $team->id,
            'email' => 'member@test.com',
        ]);

        $this->postJson(route('login'), [
            'email' => 'member@test.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_json_dashboard_returns_401_without_token(): void
    {
        $this->getJson(route('dashboard'))->assertUnauthorized();
    }

    public function test_member_can_read_dashboard_and_update_progress_with_token(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $token = $this->tokenFor($member);

        $this->withToken($token)
            ->getJson(route('dashboard'))
            ->assertOk()
            ->assertJsonStructure([
                'today',
                'personal_progress' => ['overall_rate', 'done_count', 'total_count', 'tasks'],
                'led_teams',
            ]);

        $this->withToken($token)
            ->getJson(route('tasks.index'))
            ->assertOk()
            ->assertJsonFragment(['title' => $task->title]);

        $this->withToken($token)
            ->patchJson(route('tasks.update-progress', $task), [
                'status' => TaskStatus::InProgress->value,
                'progress_done' => 'Built the login form',
                'progress_next' => 'Connect API endpoints',
            ])
            ->assertOk()
            ->assertJsonPath('task.status', TaskStatus::InProgress->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
            'progress_done' => 'Built the login form',
        ]);
    }

    public function test_member_json_progress_requires_blocker_note_when_blocked(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->withToken($this->tokenFor($member))
            ->patchJson(route('tasks.update-progress', $task), [
                'status' => TaskStatus::Blocked->value,
                'progress_done' => 'Started work',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('blocker_note');
    }

    public function test_admin_json_request_to_member_tasks_returns_403(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withToken($this->tokenFor($admin))
            ->getJson(route('tasks.index'))
            ->assertForbidden();
    }

    public function test_member_json_request_to_projects_returns_403(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->withToken($this->tokenFor($member))
            ->getJson(route('projects.index'))
            ->assertForbidden();
    }

    public function test_team_lead_can_create_task_via_json(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $project = Project::factory()->create();
        $project->teams()->attach($team->id);

        $this->withToken($this->tokenFor($lead))
            ->postJson(route('tasks.store', $project), [
                'title' => 'Build login page',
                'description' => 'Implement authentication UI.',
                'assigned_to' => $member->id,
                'status' => TaskStatus::Todo->value,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Build login page');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'assigned_to' => $member->id,
            'title' => 'Build login page',
        ]);
    }

    public function test_team_lead_can_list_team_tasks_via_json(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->withToken($this->tokenFor($lead))
            ->getJson(route('team.tasks'))
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonFragment(['title' => $task->title]);
    }

    public function test_admin_can_create_user_via_json(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        $this->withToken($this->tokenFor($admin))
            ->postJson(route('admin.users.store'), [
                'name' => 'Ada Lovelace',
                'job_title' => 'Developer',
                'email' => 'ada@briefly.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'team_ids' => [$team->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'ada@briefly.test');

        $this->assertDatabaseHas('users', [
            'email' => 'ada@briefly.test',
            'name' => 'Ada Lovelace',
        ]);
    }

    public function test_admin_can_create_team_and_project_via_json(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->withToken($this->tokenFor($admin))
            ->postJson(route('admin.teams.store'), [
                'name' => 'Mobile Squad',
                'user_ids' => [$member->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Mobile Squad');

        $team = Team::query()->where('name', 'Mobile Squad')->firstOrFail();

        $this->withToken($this->tokenFor($admin))
            ->postJson(route('admin.projects.store'), [
                'name' => 'Briefly Mobile',
                'description' => 'Flutter client',
                'team_ids' => [$team->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Briefly Mobile');

        $this->assertDatabaseHas('projects', ['name' => 'Briefly Mobile']);
    }

    public function test_json_logout_revokes_current_token(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $token = $this->tokenFor($member);

        $this->withToken($token)
            ->postJson(route('logout'))
            ->assertNoContent();

        $this->assertSame(0, $member->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson(route('dashboard'))
            ->assertUnauthorized();
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }
}
