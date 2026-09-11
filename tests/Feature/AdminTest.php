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
            ->assertSee(__('Overview'));
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
            ->assertRedirect(route('admin.projects.index'));

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
                'team_ids' => [$teamA->id, $teamB->id],
                'role' => UserRole::TeamLead->value,
            ])
            ->assertRedirect(route('admin.users.show', $member));

        $member->refresh();

        $this->assertEqualsCanonicalizing(
            [$teamA->id, $teamB->id],
            $member->teams()->pluck('teams.id')->all()
        );
        $this->assertSame(UserRole::TeamLead, $member->role);
    }

    public function test_admin_can_view_team_detail_and_sync_users(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create(['name' => 'Platform Team']);
        $member = User::factory()->create(['team_id' => $team->id, 'name' => 'Emma Nguyen']);

        $this->actingAs($admin)
            ->get(route('admin.teams.show', $team))
            ->assertOk()
            ->assertSee('Platform Team');

        $this->actingAs($admin)
            ->put(route('admin.teams.update-users', $team), [
                'user_ids' => [$member->id],
            ])
            ->assertRedirect(route('admin.teams.show', $team));

        $this->assertTrue($member->fresh()->teams()->where('teams.id', $team->id)->exists());
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
                'role' => UserRole::Member->value,
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
                'role' => UserRole::Member->value,
            ])
            ->assertForbidden();
    }
}
