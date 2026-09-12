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
            ->assertSee(__('Overview'))
            ->assertSee(__('Completion rate'))
            ->assertSee(__('Completion rate by team'))
            ->assertSee(__('Completion rate by project'));
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
            ->get(route('tasks.my'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_create_and_update_project(): void
    {
        $admin = User::factory()->admin()->create();
        $teamA = Team::factory()->create(['name' => 'Alpha Team']);
        $teamB = Team::factory()->create(['name' => 'Beta Team']);

        $this->actingAs($admin)
            ->post(route('admin.projects.store'), [
                'name' => 'Metrics Dashboard',
                'description' => 'Reporting for leadership.',
                'team_ids' => [$teamA->id, $teamB->id],
            ])
            ->assertRedirect(route('admin.projects.index'));

        $project = Project::where('name', 'Metrics Dashboard')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.projects.update', $project), [
                'name' => 'Metrics Dashboard',
                'description' => 'Updated description.',
                'team_ids' => [$teamA->id],
            ])
            ->assertRedirect(route('admin.projects.edit', $project));

        $this->assertEqualsCanonicalizing([$teamA->id], $project->fresh()->teams()->pluck('teams.id')->all());
    }

    public function test_team_lead_cannot_create_admin_project(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        $this->actingAs($lead)
            ->post(route('admin.projects.store'), [
                'name' => 'Unauthorized Project',
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

    public function test_admin_can_assign_user_to_multiple_teams(): void
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
                'name' => $member->name,
                'team_ids' => [$teamA->id, $teamB->id],
            ])
            ->assertRedirect(route('admin.users.show', $member));

        $member->refresh();

        $this->assertEqualsCanonicalizing(
            [$teamA->id, $teamB->id],
            $member->teams()->pluck('teams.id')->all()
        );
        $this->assertSame(UserRole::Member, $member->role);
    }

    public function test_admin_can_edit_team_and_sync_members_and_projects(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create(['name' => 'Platform Team']);
        $member = User::factory()->create(['team_id' => $team->id, 'name' => 'Emma Nguyen']);
        $project = Project::factory()->create(['name' => 'Metrics Dashboard']);

        $this->actingAs($admin)
            ->get(route('admin.teams.edit', $team))
            ->assertOk()
            ->assertSee('Platform Team')
            ->assertSee(__('Team members'));

        $this->actingAs($admin)
            ->put(route('admin.teams.update', $team), [
                'name' => 'Platform Team',
                'user_ids' => [$member->id],
                'project_ids' => [$project->id],
            ])
            ->assertRedirect(route('admin.teams.edit', $team));

        $this->assertTrue($member->fresh()->teams()->where('teams.id', $team->id)->exists());
        $this->assertTrue($team->fresh()->projects()->where('projects.id', $project->id)->exists());
    }

    public function test_admin_team_show_url_redirects_to_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create(['name' => 'Platform Team']);

        $this->actingAs($admin)
            ->get(route('admin.teams.show', $team))
            ->assertRedirect(route('admin.teams.edit', $team));
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
    }

    public function test_admin_can_create_team_with_team_lead_and_members(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = User::factory()->create(['name' => 'Sophie Laurent']);
        $member = User::factory()->create(['name' => 'Emma Nguyen']);

        $this->actingAs($admin)
            ->post(route('admin.teams.store'), [
                'name' => 'Platform Team',
                'team_lead_id' => $lead->id,
                'user_ids' => [$member->id],
            ])
            ->assertRedirect(route('admin.teams.index'));

        $team = Team::where('name', 'Platform Team')->firstOrFail();

        $this->assertSame(UserRole::TeamLead, $lead->fresh()->role);
        $this->assertSame($team->id, $lead->fresh()->team_id);
        $this->assertTrue($member->fresh()->teams()->where('teams.id', $team->id)->exists());
        $this->assertTrue($lead->fresh()->isTeamLeadOf($team));
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $lead->id,
            'is_team_lead' => true,
        ]);
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $member->id,
            'is_team_lead' => false,
        ]);
    }

    public function test_admin_can_create_user_with_multiple_teams(): void
    {
        $admin = User::factory()->admin()->create();
        $teamA = Team::factory()->create(['name' => 'Alpha Team']);
        $teamB = Team::factory()->create(['name' => 'Beta Team']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Lucas Martin',
                'email' => 'lucas.martin@briefly.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'team_ids' => [$teamA->id, $teamB->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'lucas.martin@briefly.test')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$teamA->id, $teamB->id],
            $user->teams()->pluck('teams.id')->all()
        );
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
                'team_ids' => [$team->id],
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_remove_team_lead_from_their_last_team(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create(['name' => 'Platform Team']);
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        $this->actingAs($admin)
            ->put(route('admin.teams.update', $team), [
                'name' => 'Platform Team',
                'user_ids' => [],
            ])
            ->assertSessionHasErrors('user_ids');

        $this->assertSame($team->id, $lead->fresh()->team_id);
        $this->assertSame(UserRole::TeamLead, $lead->fresh()->role);
    }

    public function test_admin_can_assign_user_as_team_lead_of_multiple_teams(): void
    {
        $admin = User::factory()->admin()->create();
        $teamA = Team::factory()->create(['name' => 'Alpha Team']);
        $teamB = Team::factory()->create(['name' => 'Beta Team']);
        $user = User::factory()->create([
            'team_id' => null,
            'role' => UserRole::Member,
        ]);
        $user->teams()->sync([$teamB->id => ['is_team_lead' => false]]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'team_ids' => [$teamA->id, $teamB->id],
                'team_lead_ids' => [$teamA->id, $teamB->id],
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();

        $this->assertSame(UserRole::TeamLead, $user->role);
        $this->assertEqualsCanonicalizing([$teamA->id, $teamB->id], $user->teams()->pluck('teams.id')->all());
        $this->assertTrue($user->isTeamLeadOf($teamA));
        $this->assertTrue($user->isTeamLeadOf($teamB));
    }
}
