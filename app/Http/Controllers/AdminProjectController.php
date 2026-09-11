<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminStoreProjectRequest;
use App\Http\Requests\AdminUpdateProjectRequest;
use App\Models\Project;
use App\Models\Team;
use App\Services\AdminProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function __construct(private AdminProjectService $adminProjectService) {}

    public function index(): View
    {
        $projects = $this->adminProjectService->projects();

        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        $teams = Team::query()->orderBy('name')->get();

        return view('admin.projects.create', compact('teams'));
    }

    public function store(AdminStoreProjectRequest $request): RedirectResponse
    {
        $this->adminProjectService->createProject($request->validated());

        return redirect()->route('admin.projects.index')
            ->with('success', __('Project created successfully.'));
    }

    public function edit(Project $project): View
    {
        $project->load('teams');
        $teams = Team::query()->orderBy('name')->get();
        $selectedTeamIds = $project->teams->pluck('id')->all();

        return view('admin.projects.edit', compact('project', 'teams', 'selectedTeamIds'));
    }

    public function update(AdminUpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->adminProjectService->updateProject($project, $request->validated());

        return redirect()->route('admin.projects.index')
            ->with('success', __('Project updated successfully.'));
    }
}
