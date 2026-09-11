<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Services\AdminTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminTeamController extends Controller
{
    public function __construct(private AdminTeamService $adminTeamService) {}

    public function index(): View
    {
        $teams = $this->adminTeamService->teams();

        return view('admin.teams.index', compact('teams'));
    }

    public function create(): View
    {
        return view('admin.teams.create');
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->adminTeamService->createTeam($request->validated('name'));

        return redirect()->route('admin.teams.index')
            ->with('success', __('Team created successfully.'));
    }
}
