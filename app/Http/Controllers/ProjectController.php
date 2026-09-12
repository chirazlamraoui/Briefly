<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        $managedTeamIds = auth()->user()->managedTeamIds();

        $projects = Project::query()
            ->whereHas('teams', fn ($query) => $query->whereIn('teams.id', $managedTeamIds))
            ->with(['teams' => fn ($query) => $query->whereIn('teams.id', $managedTeamIds)])
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $managedTeamIds = auth()->user()->managedTeamIds();
        $contextTeams = Team::query()
            ->whereIn('id', $managedTeamIds)
            ->whereHas('projects', fn ($query) => $query->where('projects.id', $project->id))
            ->orderBy('name')
            ->get();

        abort_if($contextTeams->isEmpty(), 403);

        $contextTeam = $contextTeams->first();
        $tasks = $this->taskService->teamTasksForProject($contextTeam, $project);
        $members = $this->taskService->assignableMembers($contextTeam);

        return view('projects.show', compact('project', 'tasks', 'members', 'contextTeam', 'contextTeams'));
    }
}
