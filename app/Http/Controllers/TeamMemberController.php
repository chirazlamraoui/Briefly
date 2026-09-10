<?php

namespace App\Http\Controllers;

use App\Models\DailyUpdate;
use App\Models\User;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function show(User $user): View
    {
        $lead = auth()->user();

        abort_unless($lead->isTeamLead(), 403);
        abort_unless($user->team_id === $lead->team_id, 403);

        $updates = DailyUpdate::query()
            ->with(['blocker', 'task.project'])
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->paginate(15);

        return view('team.member', compact('user', 'updates'));
    }
}
