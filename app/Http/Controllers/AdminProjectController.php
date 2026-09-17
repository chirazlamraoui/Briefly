<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminStoreProjectRequest;
use App\Http\Requests\AdminUpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TeamResource;
use App\Models\Project;
use App\Models\Team;
use App\Services\AdminProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin create / edit projects. */
class AdminProjectController extends Controller
{
    public function __construct(private AdminProjectService $adminProjectService) {}

    public function index(Request $request): View|JsonResponse
    {
        $projects = $this->adminProjectService->projects();

        return $this->respond($request, view('admin.projects.index', compact('projects')), ProjectResource::collection($projects));
    }

    public function create(Request $request): View|JsonResponse
    {
        $teams = Team::query()->orderBy('name')->get();

        return $this->respond($request, view('admin.projects.create', compact('teams')), [
            'teams' => TeamResource::collection($teams)->resolve(),
        ]);
    }

    public function store(AdminStoreProjectRequest $request): RedirectResponse|JsonResponse
    {
        $project = $this->adminProjectService->createProject($request->validated());
        $project->load('teams');

        return $this->respond($request, redirect()->route('admin.projects.index')
            ->with('success', __('Project created successfully.')), new ProjectResource($project), 201);
    }

    public function edit(Request $request, Project $project): View|JsonResponse
    {
        $project->load('teams');
        $teams = Team::query()->orderBy('name')->get();
        $selectedTeamIds = $project->teams->pluck('id')->all();

        return $this->respond($request, view('admin.projects.edit', compact('project', 'teams', 'selectedTeamIds')), [
            'project' => (new ProjectResource($project))->resolve(),
            'teams' => TeamResource::collection($teams)->resolve(),
            'selected_team_ids' => $selectedTeamIds,
        ]);
    }

    public function update(AdminUpdateProjectRequest $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->adminProjectService->updateProject($project, $request->validated());
        $project->refresh()->load('teams');

        return $this->respond($request, redirect()->route('admin.projects.edit', $project)
            ->with('success', __('Project updated successfully.')), [
                'project' => (new ProjectResource($project))->resolve(),
                'message' => __('Project updated successfully.'),
            ]);
    }
}
