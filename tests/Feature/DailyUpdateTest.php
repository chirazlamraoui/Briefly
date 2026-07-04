<?php

namespace Tests\Feature;

use App\Enums\UpdateStatus;
use App\Enums\UserRole;
use App\Models\DailyUpdate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_daily_update(): void
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

        $this->assertDatabaseHas('updates', [
            'user_id' => $member->id,
        ]);
    }

    public function test_member_cannot_create_second_update_same_day(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        DailyUpdate::create([
            'user_id' => $member->id,
            'date' => today(),
            'content' => ['done' => 'x', 'in_progress' => 'y', 'blocker' => ''],
            'status' => UpdateStatus::Green,
        ]);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'done' => 'Another',
                'in_progress' => 'Another',
                'blocker_type' => 'none',
                'status' => UpdateStatus::Green->value,
            ])
            ->assertRedirect(route('daily-update.edit'));
    }

    public function test_team_lead_can_view_team_updates(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        DailyUpdate::create([
            'user_id' => $member->id,
            'date' => today(),
            'content' => ['done' => 'Done', 'in_progress' => 'IP', 'blocker' => ''],
            'status' => UpdateStatus::Orange,
        ]);

        $this->actingAs($lead)
            ->get(route('team.updates'))
            ->assertOk()
            ->assertSee('Done');
    }

    public function test_member_cannot_view_team_updates_page(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->get(route('team.updates'))
            ->assertForbidden();
    }
}
