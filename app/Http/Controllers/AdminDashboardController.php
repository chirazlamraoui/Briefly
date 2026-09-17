<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TeamResource;
use App\Services\AdminService;
use App\Services\AdminTeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin home: counts, completion, teams. */
class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminService $adminService,
        private AdminTeamService $adminTeamService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $stats = $this->adminService->overviewStats();
        $completion = $this->adminService->completionOverview();
        $teams = $this->adminTeamService->teams();
        $projects = $this->adminService->projectsOverview();

        return $this->respond($request, view('admin.dashboard', compact('stats', 'completion', 'teams', 'projects')), [
            'stats' => $stats,
            'completion' => $completion,
            'teams' => TeamResource::collection($teams)->resolve(),
            'projects' => ProjectResource::collection($projects)->resolve(),
        ]);
    }
}
