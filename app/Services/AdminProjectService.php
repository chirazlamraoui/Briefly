<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class AdminProjectService
{
    /**
     * @return Collection<int, Project>
     */
    public function projects(): Collection
    {
        return Project::query()
            ->with('teams')
            ->withCount(['teams', 'tasks'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array{name: string, description?: string|null, team_ids?: list<int>}  $data
     */
    public function createProject(array $data): Project
    {
        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $project->teams()->sync($data['team_ids'] ?? []);

        return $project->load('teams');
    }

    /**
     * @param  array{name: string, description?: string|null, team_ids?: list<int>}  $data
     */
    public function updateProject(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $project->teams()->sync($data['team_ids'] ?? []);

        return $project->fresh(['teams']);
    }
}
