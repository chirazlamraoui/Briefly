<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyUpdateRequest;
use App\Models\Blocker;
use App\Models\DailyUpdate;
use App\Services\BlockerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DailyUpdateController extends Controller
{
    public function __construct(private BlockerService $blockerService)
    {
    }

    public function history(): View
    {
        $updates = DailyUpdate::query()
            ->with('blocker')
            ->where('user_id', auth()->id())
            ->orderByDesc('date')
            ->paginate(15);

        return view('daily-update.history', compact('updates'));
    }

    public function edit(): View
    {
        $user = auth()->user();

        $update = DailyUpdate::query()
            ->with('blocker')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $teamBlockers = Blocker::query()
            ->where('team_id', $user->team_id)
            ->orderBy('label')
            ->get();

        return view('daily-update.form', compact('update', 'teamBlockers'));
    }

    public function store(StoreDailyUpdateRequest $request): RedirectResponse
    {
        $existing = DailyUpdate::query()
            ->where('user_id', auth()->id())
            ->whereDate('date', today())
            ->first();

        if ($existing) {
            return redirect()->route('daily-update.edit')
                ->with('error', __('You already submitted today\'s update. Please edit it instead.'));
        }

        $user = auth()->user();
        $blockerId = $this->blockerService->resolveBlockerId($user, $request->validated());

        DailyUpdate::create([
            'user_id' => $user->id,
            'date' => today(),
            'content' => [
                'done' => $request->validated('done'),
                'in_progress' => $request->validated('in_progress'),
                'blocker' => '',
            ],
            'status' => $request->validated('status'),
            'blocker_id' => $blockerId,
        ]);

        return redirect()->route('dashboard')->with('success', __('Daily update saved.'));
    }

    public function update(StoreDailyUpdateRequest $request): RedirectResponse
    {
        $update = DailyUpdate::query()
            ->where('user_id', auth()->id())
            ->whereDate('date', today())
            ->firstOrFail();

        $this->authorize('update', $update);

        $blockerId = $this->blockerService->resolveBlockerId(auth()->user(), $request->validated());

        $update->update([
            'content' => [
                'done' => $request->validated('done'),
                'in_progress' => $request->validated('in_progress'),
                'blocker' => '',
            ],
            'status' => $request->validated('status'),
            'blocker_id' => $blockerId,
        ]);

        return redirect()->route('dashboard')->with('success', __('Daily update updated.'));
    }
}
