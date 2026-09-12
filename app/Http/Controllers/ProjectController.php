<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\TaskService;
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
