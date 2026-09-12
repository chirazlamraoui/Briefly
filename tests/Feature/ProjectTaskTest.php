<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
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

    public function test_member_can_view_task_details(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee($task->description)
            ->assertSee(__('Update progress'));
    }

    public function test_member_can_save_task_progress(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::InProgress->value,
                'progress_done' => 'Built the login form',
                'progress_next' => 'Connect API endpoints',
            ])
            ->assertRedirect(route('tasks.show', $task));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
            'progress_done' => 'Built the login form',
        ]);

        $this->assertDatabaseHas('task_updates', [
            'task_id' => $task->id,
            'user_id' => $member->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_member_must_provide_blocker_note_when_blocked(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::Blocked->value,
                'progress_done' => 'Started work',
            ])
            ->assertSessionHasErrors('blocker_note');
    }

    public function test_member_can_mark_task_done_and_sets_completed_at(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::Done->value,
                'progress_done' => 'Task finished',
            ])
            ->assertRedirect(route('tasks.show', $task));

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_member_cannot_update_task_assigned_to_someone_else(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $otherMember = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($otherMember);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::Done->value,
            ])
            ->assertForbidden();
    }

    public function test_team_lead_can_view_team_tasks_page(): void
    {
        $team = Team::factory()->create();
        $lead = User::factory()->teamLead()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($lead)
            ->get(route('team.tasks'))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee($member->name);
    }

    public function test_project_show_lists_tasks_for_members_on_team_pivot_even_when_primary_team_differs(): void
    {
        $teamA = Team::factory()->create(['name' => 'Alpha Team']);
        $teamB = Team::factory()->create(['name' => 'Beta Team']);
        $lead = User::factory()->teamLead()->create(['team_id' => $teamB->id]);
        $member = User::factory()->create(['team_id' => $teamA->id]);
        $member->teams()->syncWithoutDetaching([$teamB->id]);

        $project = Project::factory()->create(['name' => 'Shared Platform']);
        $project->teams()->attach($teamB->id);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $member->id,
            'title' => 'Cross-team task',
        ]);

        $this->actingAs($lead)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Cross-team task');

        $this->actingAs($lead)
            ->get(route('team.tasks'))
            ->assertOk()
            ->assertSee('Cross-team task');
    }

    public function test_member_can_view_task_history(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::InProgress->value,
                'progress_done' => 'Initial progress',
            ]);

        $this->actingAs($member)
            ->get(route('tasks.history'))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee('Initial progress');
    }

    public function test_task_can_have_multiple_progress_updates(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);
        $task = $this->createTaskForMember($member);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::InProgress->value,
                'progress_done' => 'Step one',
            ]);

        $this->actingAs($member)
            ->patch(route('tasks.update-progress', $task), [
                'status' => TaskStatus::Done->value,
                'progress_done' => 'Step two',
            ]);

        $this->assertSame(2, TaskUpdate::where('task_id', $task->id)->count());
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
