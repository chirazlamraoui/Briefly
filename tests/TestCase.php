<?php

namespace Tests;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createTaskForMember(User $member, ?Project $project = null): Task
    {
        $project ??= Project::factory()->create();
        $project->teams()->syncWithoutDetaching([$member->team_id]);

        return Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $member->id,
        ]);
    }
}
