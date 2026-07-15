<?php

namespace Tests\Feature;

use App\Enums\BriefStatus;
use App\Enums\UserRole;
use App\Models\Brief;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BriefTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_lead_can_publish_brief(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        $brief = Brief::create([
            'team_id' => $team->id,
            'date' => today(),
            'content' => ['done' => 'D', 'in_progress' => 'P', 'blocker' => ''],
            'status' => BriefStatus::Draft,
            'created_by' => $lead->id,
        ]);

        $this->actingAs($lead)
            ->post(route('briefs.publish', $brief))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($brief->fresh()->isPublished());
    }

    public function test_member_cannot_view_draft_brief(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        $brief = Brief::create([
            'team_id' => $team->id,
            'date' => today(),
            'content' => ['done' => 'Secret', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Draft,
            'created_by' => $lead->id,
        ]);

        $this->actingAs($member)
            ->get(route('briefs.show', $brief))
            ->assertForbidden();
    }

    public function test_member_can_view_published_brief_from_own_team(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        $brief = Brief::create([
            'team_id' => $team->id,
            'date' => today(),
            'content' => ['done' => 'Published content', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Published,
            'created_by' => $lead->id,
            'published_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('briefs.show', $brief))
            ->assertOk()
            ->assertSee('Published content');
    }

    public function test_user_cannot_view_brief_from_other_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $leadA = User::factory()->teamLead()->create(['team_id' => $teamA->id]);
        $memberB = User::factory()->create(['team_id' => $teamB->id]);

        $brief = Brief::create([
            'team_id' => $teamA->id,
            'date' => today(),
            'content' => ['done' => 'Team A only', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Published,
            'created_by' => $leadA->id,
            'published_at' => now(),
        ]);

        $this->actingAs($memberB)
            ->get(route('briefs.show', $brief))
            ->assertForbidden();
    }

    public function test_history_search_finds_brief_by_keyword(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        Brief::create([
            'team_id' => $team->id,
            'date' => today()->subDays(2),
            'content' => ['done' => 'UniqueKeywordXYZ', 'in_progress' => '', 'blocker' => ''],
            'status' => BriefStatus::Published,
            'created_by' => $lead->id,
            'published_at' => now(),
        ]);

        $this->actingAs($lead)
            ->get(route('history.index', ['q' => 'UniqueKeywordXYZ']))
            ->assertOk()
            ->assertSee('UniqueKeywordXYZ');
    }
}
