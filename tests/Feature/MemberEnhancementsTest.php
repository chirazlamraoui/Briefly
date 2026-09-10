<?php

namespace Tests\Feature;

use App\Enums\BriefStatus;
use App\Models\Brief;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_own_update_history(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->get(route('daily-update.history'))
            ->assertOk()
            ->assertSee(__('My update history'));
    }

    public function test_dashboard_shows_reminder_when_update_missing(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Reminder: submit your daily update'), false);
    }

    public function test_member_can_download_published_brief_pdf(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        $brief = Brief::create([
            'team_id' => $team->id,
            'date' => today(),
            'content' => [
                'done' => 'Shipped feature',
                'in_progress' => 'Testing',
                'blocker' => 'None',
            ],
            'status' => BriefStatus::Published,
            'created_by' => $lead->id,
            'published_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('briefs.export.pdf', $brief))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_brief_show_page_has_copy_and_pdf_actions(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        $brief = Brief::create([
            'team_id' => $team->id,
            'date' => today(),
            'content' => [
                'done' => 'Done item',
                'in_progress' => 'In progress item',
                'blocker' => 'Blocker item',
            ],
            'status' => BriefStatus::Published,
            'created_by' => $lead->id,
            'published_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('briefs.show', $brief))
            ->assertOk()
            ->assertSee(__('Copy brief'), false)
            ->assertSee(__('Download PDF'), false);
    }
}
