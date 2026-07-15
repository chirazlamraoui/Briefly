<?php

namespace App\Http\Controllers;

use App\Services\BriefService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamUpdateController extends Controller
{
    public function __construct(private BriefService $briefService)
    {
    }

    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : today();

        $data = $this->briefService->teamUpdatesForDate(auth()->user()->team, $date);

        return view('team.updates', [
            'date' => $date,
            'members' => $data['members'],
            'updates' => $data['updates'],
            'missing' => $data['missing'],
        ]);
    }
}
