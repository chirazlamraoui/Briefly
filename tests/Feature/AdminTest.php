<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('Administration'));
    }

    public function test_member_cannot_access_admin_dashboard(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_is_redirected_from_member_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('daily-update.edit'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_update_project_teams(): void
    {
        $admin = User::factory()->admin()->create();
        $teamA = Team::factory()->create(['name' => 'Alpha Team']);
        $teamB = Team::factory()->create(['name' => 'Beta Team']);
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.projects.update-teams', $project), [
                'team_ids' => [$teamA->id, $teamB->id],
            ])
            ->assertRedirect(route('admin.projects.index'));

        $this->assertEqualsCanonicalizing(
            [$teamA->id, $teamB->id],
            $project->fresh()->teams()->pluck('teams.id')->all()
        );
    }

    public function test_team_lead_cannot_update_project_teams(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $project = Project::factory()->create();

        $this->actingAs($lead)
            ->put(route('admin.projects.update-teams', $project), [
                'team_ids' => [$team->id],
            ])
            ->assertForbidden();
    }

    public function test_login_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@briefly.test',
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_assign_user_to_team_and_role(): void
    {
        $admin = User::factory()->admin()->create();
        $teamA = Team::factory()->create(['name' => 'Alpha Team']);
        $teamB = Team::factory()->create(['name' => 'Beta Team']);
        $member = User::factory()->create([
            'team_id' => $teamA->id,
            'role' => UserRole::Member,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member), [
                'team_id' => $teamB->id,
                'role' => UserRole::TeamLead->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $member->refresh();

        $this->assertSame($teamB->id, $member->team_id);
        $this->assertSame(UserRole::TeamLead, $member->role);
    }

    public function test_admin_can_view_teams_management(): void
    {
        $admin = User::factory()->admin()->create();
        Team::factory()->create(['name' => 'Platform Team']);

        $this->actingAs($admin)
            ->get(route('admin.teams.index'))
            ->assertOk()
            ->assertSee(__('Team management'))
            ->assertSee('Platform Team');
    }

    public function test_admin_can_create_team(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.teams.store'), [
                'name' => 'Design Team',
            ])
            ->assertRedirect(route('admin.teams.index'));

        $this->assertDatabaseHas('teams', ['name' => 'Design Team']);
        $this->assertGreaterThan(0, \App\Models\Blocker::where('team_id', Team::where('name', 'Design Team')->value('id'))->count());
    }

    public function test_admin_cannot_create_duplicate_team(): void
    {
        $admin = User::factory()->admin()->create();
        Team::factory()->create(['name' => 'Design Team']);

        $this->actingAs($admin)
            ->post(route('admin.teams.store'), [
                'name' => 'Design Team',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_member_cannot_create_team(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->post(route('admin.teams.store'), [
                'name' => 'Unauthorized Team',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create(['name' => 'Support Team']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Member',
                'email' => 'new.member@briefly.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'team_id' => $team->id,
                'role' => UserRole::Member->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.member@briefly.test',
            'team_id' => $team->id,
            'role' => UserRole::Member->value,
        ]);
    }

    public function test_admin_cannot_create_user_with_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        User::factory()->create(['email' => 'taken@briefly.test', 'team_id' => $team->id]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate',
                'email' => 'taken@briefly.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'team_id' => $team->id,
                'role' => UserRole::Member->value,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_member_cannot_create_user(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->post(route('admin.users.store'), [
                'name' => 'Blocked',
                'email' => 'blocked@briefly.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'team_id' => $team->id,
                'role' => UserRole::Member->value,
            ])
            ->assertForbidden();
    }
}
