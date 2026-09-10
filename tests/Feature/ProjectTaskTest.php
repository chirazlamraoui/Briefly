<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UpdateStatus;
use App\Models\DailyUpdate;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_lead_can_create_project(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);

        $this->actingAs($lead)
            ->post(route('projects.store'), [
                'name' => 'Website Redesign',
                'description' => 'Main product website refresh.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['name' => 'Website Redesign']);
        $this->assertDatabaseHas('project_team', [
            'team_id' => $team->id,
        ]);
    }

    public function test_team_lead_can_create_task_for_team_member(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $project = Project::factory()->create();
        $project->teams()->attach($team->id);

        $this->actingAs($lead)
            ->post(route('tasks.store', $project), [
                'title' => 'Build login page',
                'description' => 'Implement authentication UI.',
                'assigned_to' => $member->id,
                'status' => TaskStatus::Todo->value,
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'assigned_to' => $member->id,
            'title' => 'Build login page',
            'status' => TaskStatus::Todo->value,
        ]);
    }

    public function test_team_lead_cannot_assign_task_to_member_from_another_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $teamA->id]);
        $otherMember = User::factory()->create(['team_id' => $teamB->id]);
        $project = Project::factory()->create();
        $project->teams()->attach($teamA->id);

        $this->actingAs($lead)
            ->post(route('tasks.store', $project), [
                'title' => 'Invalid assignment',
                'assigned_to' => $otherMember->id,
                'status' => TaskStatus::Todo->value,
            ])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_member_can_view_assigned_tasks(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->get(route('tasks.my'))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee($task->project->name);
    }

    public function test_member_must_select_task_when_submitting_daily_update(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'task_id' => $task->id,
                'done' => 'Finished task A',
                'in_progress' => 'Working on task B',
                'blocker_type' => 'none',
                'status' => UpdateStatus::Green->value,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('updates', [
            'user_id' => $member->id,
            'task_id' => $task->id,
        ]);
    }

    public function test_member_cannot_report_on_task_assigned_to_someone_else(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $otherMember = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($otherMember);

        $this->actingAs($member)
            ->post(route('daily-update.store'), [
                'task_id' => $task->id,
                'done' => 'Finished task A',
                'in_progress' => 'Working on task B',
                'blocker_type' => 'none',
                'status' => UpdateStatus::Green->value,
            ])
            ->assertSessionHasErrors('task_id');
    }

    public function test_task_can_have_multiple_daily_updates_on_different_days(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        DailyUpdate::create([
            'user_id' => $member->id,
            'task_id' => $task->id,
            'date' => today(),
            'content' => ['done' => 'Day 1', 'in_progress' => 'Day 1', 'blocker' => ''],
            'status' => UpdateStatus::Green,
        ]);

        DailyUpdate::create([
            'user_id' => $member->id,
            'task_id' => $task->id,
            'date' => today()->subDay(),
            'content' => ['done' => 'Day 0', 'in_progress' => 'Day 0', 'blocker' => ''],
            'status' => UpdateStatus::Green,
        ]);

        $this->assertSame(2, DailyUpdate::where('task_id', $task->id)->count());
    }

    public function test_member_cannot_access_project_management(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->get(route('projects.index'))
            ->assertForbidden();
    }
}
