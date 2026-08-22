<?php

namespace Tests\Feature;

use App\Enums\BriefStatus;
use App\Enums\UserRole;
use App\Models\Brief;
use App\Models\DailyUpdate;
use App\Models\Team;
use App\Models\User;
use App\Enums\UpdateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtendedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_updates_user_info(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id, 'name' => 'Old Name']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'New Name',
                'email' => $user->email,
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_forgot_password_page_is_accessible(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_team_lead_can_preview_draft_brief(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        $brief = Brief::create([
            'team_id' => $team->id,
            'date' => today(),
            'content' => ['done' => 'Preview content', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Draft,
            'created_by' => $lead->id,
        ]);

        $this->actingAs($lead)
            ->get(route('briefs.preview', $brief))
            ->assertOk()
            ->assertSee('Preview content');
    }

    public function test_history_supports_date_range_and_author_filters(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        Brief::create([
            'team_id' => $team->id,
            'date' => today()->subDays(5),
            'content' => ['done' => 'Old brief', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Published,
            'created_by' => $lead->id,
            'published_at' => now(),
        ]);

        Brief::create([
            'team_id' => $team->id,
            'date' => today()->subDay(),
            'content' => ['done' => 'Recent brief', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Published,
            'created_by' => $lead->id,
            'published_at' => now(),
        ]);

        $this->actingAs($lead)
            ->get(route('history.index', [
                'date_from' => today()->subDays(2)->format('Y-m-d'),
                'author_id' => $lead->id,
            ]))
            ->assertOk()
            ->assertSee('Recent brief')
            ->assertDontSee('Old brief');
    }

    public function test_team_lead_can_view_member_detail(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        DailyUpdate::create([
            'user_id' => $member->id,
            'date' => today(),
            'content' => ['done' => 'Member work', 'in_progress' => '', 'blocker' => ''],
            'status' => UpdateStatus::Green,
        ]);

        $this->actingAs($lead)
            ->get(route('team.members.show', $member))
            ->assertOk()
            ->assertSee('Member work');
    }

    public function test_member_cannot_view_other_team_member_detail(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $leadA = User::factory()->teamLead()->create(['team_id' => $teamA->id]);
        $memberB = User::factory()->create(['team_id' => $teamB->id]);

        $this->actingAs($leadA)
            ->get(route('team.members.show', $memberB))
            ->assertForbidden();
    }
}
