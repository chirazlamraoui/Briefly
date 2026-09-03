<?php

namespace Tests\Feature;

use App\Enums\UpdateStatus;
use App\Models\Blocker;
use App\Models\DailyUpdate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockerSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_submit_update_with_no_blocker(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'done' => 'Finished task A',
                'in_progress' => 'Working on task B',
                'blocker_type' => 'none',
                'status' => UpdateStatus::Green->value,
            ])
            ->assertRedirect(route('dashboard'));

        $update = DailyUpdate::first();

        $this->assertNull($update->blocker_id);
        $this->assertSame('', $update->blockerLabel());
    }

    public function test_member_can_select_existing_team_blocker(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $blocker = Blocker::create(['team_id' => $team->id, 'label' => 'Waiting for API access']);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'done' => 'Finished task A',
                'in_progress' => 'Working on task B',
                'blocker_type' => 'existing',
                'blocker_id' => $blocker->id,
                'status' => UpdateStatus::Orange->value,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('updates', [
            'user_id' => $member->id,
            'blocker_id' => $blocker->id,
        ]);
    }

    public function test_member_can_add_new_team_blocker(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'done' => 'Finished task A',
                'in_progress' => 'Working on task B',
                'blocker_type' => 'new',
                'new_blocker' => 'Design review pending',
                'status' => UpdateStatus::Red->value,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('blockers', [
            'team_id' => $team->id,
            'label' => 'Design review pending',
        ]);

        $blocker = Blocker::where('label', 'Design review pending')->first();

        $this->assertDatabaseHas('updates', [
            'user_id' => $member->id,
            'blocker_id' => $blocker->id,
        ]);
    }

    public function test_member_cannot_select_blocker_from_another_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $teamA->id]);
        $otherBlocker = Blocker::create(['team_id' => $teamB->id, 'label' => 'Other team blocker']);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'done' => 'Finished task A',
                'in_progress' => 'Working on task B',
                'blocker_type' => 'existing',
                'blocker_id' => $otherBlocker->id,
                'status' => UpdateStatus::Red->value,
            ])
            ->assertSessionHasErrors('blocker_id');
    }

    public function test_team_lead_dashboard_shows_repeated_blockers_chart(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $blocker = Blocker::create(['team_id' => $team->id, 'label' => 'Waiting for API access']);

        foreach (range(0, 2) as $day) {
            DailyUpdate::create([
                'user_id' => $member->id,
                'date' => today()->subDays($day),
                'content' => ['done' => 'x', 'in_progress' => 'y', 'blocker' => ''],
                'status' => UpdateStatus::Orange,
                'blocker_id' => $blocker->id,
            ]);
        }

        $this->actingAs($lead)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('blockerChart', false)
            ->assertSee('Waiting for API access');
    }
}
