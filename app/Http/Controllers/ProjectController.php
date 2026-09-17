<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\Team;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Team-lead project list and project detail. */
class ProjectController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $managedTeamIds = auth()->user()->managedTeamIds();

        $projects = Project::query()
            ->whereHas('teams', fn ($query) => $query->whereIn('teams.id', $managedTeamIds))
            ->with(['teams' => fn ($query) => $query->whereIn('teams.id', $managedTeamIds)])
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return $this->respond($request, view('projects.index', compact('projects')), ProjectResource::collection($projects));
    }

    public function show(Request $request, Project $project): View|JsonResponse
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
        $project->load('teams');

        return $this->respond($request, view('projects.show', compact('project', 'tasks', 'members', 'contextTeam', 'contextTeams')), [
            'project' => (new ProjectResource($project))->resolve(),
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'members' => UserResource::collection($members)->resolve(),
            'context_team' => (new TeamResource($contextTeam))->resolve(),
            'context_teams' => TeamResource::collection($contextTeams)->resolve(),
        ]);
    }
}
