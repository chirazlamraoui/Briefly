<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        $team = auth()->user()->primaryTeam();

        abort_if($team === null, 403);

        $projects = $this->taskService->projectsForTeam($team)->loadCount('tasks');

        return view('projects.index', compact('projects', 'team'));
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = Project::create($request->validated());
        $team = auth()->user()->primaryTeam();

        abort_if($team === null, 403);

        $project->teams()->attach($team->id);

        return redirect()->route('projects.show', $project)
            ->with('success', __('Project created successfully.'));
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $team = auth()->user()->primaryTeam();

        abort_if($team === null, 403);

        $tasks = $this->taskService->teamTasksForProject($team, $project);
        $members = $this->taskService->assignableMembers($team);

        return view('projects.show', compact('project', 'tasks', 'members', 'team'));
    }
}
