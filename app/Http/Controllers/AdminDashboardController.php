<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private AdminService $adminService) {}

    public function index(): View
    {
        $stats = $this->adminService->overviewStats();
        $completion = $this->adminService->completionOverview();
        $teams = $this->adminService->teamsOverview();
        $projects = $this->adminService->projectsOverview();

        return view('admin.dashboard', compact('stats', 'completion', 'teams', 'projects'));
    }
}
