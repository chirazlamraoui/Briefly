<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectTeamsRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->with('teams')
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function edit(Project $project): View
    {
        $project->load('teams');
        $teams = Team::query()->orderBy('name')->get();
        $selectedTeamIds = $project->teams->pluck('id')->all();

        return view('admin.projects.edit', compact('project', 'teams', 'selectedTeamIds'));
    }

    public function updateTeams(UpdateProjectTeamsRequest $request, Project $project): RedirectResponse
    {
        $project->teams()->sync($request->validated('team_ids', []));

        return redirect()->route('admin.projects.index')
            ->with('success', __('Project teams updated successfully.'));
    }
}
