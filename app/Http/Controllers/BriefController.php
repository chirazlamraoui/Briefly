<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBriefRequest;
use App\Models\Brief;
use App\Services\BriefService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BriefController extends Controller
{
    public function __construct(private BriefService $briefService)
    {
    }

    public function today(): View
    {
        $user = auth()->user();

        if ($user->isTeamLead()) {
            $brief = $this->briefService->getOrCreateTodayDraft($user->team, $user);
            $this->authorize('view', $brief);

            return view('briefs.edit', compact('brief'));
        }

        $brief = $this->briefService->getPublishedForTeamOnDate($user->team_id, today());

        if (! $brief) {
            abort(404, __('No published brief for today yet.'));
        }

        $this->authorize('view', $brief);

        return view('briefs.show', compact('brief'));
    }

    public function preview(Brief $brief): View
    {
        $this->authorize('update', $brief);
        $brief->load(['team', 'author']);

        return view('briefs.preview', compact('brief'));
    }

    public function update(StoreBriefRequest $request, Brief $brief): RedirectResponse
    {
        $this->authorize('update', $brief);

        $brief->update([
            'content' => [
                'done' => $request->validated('done'),
                'in_progress' => $request->validated('in_progress'),
                'blocker' => $request->validated('blocker') ?? '',
            ],
        ]);

        return redirect()->route('briefs.today')->with('success', __('Brief draft saved.'));
    }

    public function publish(Brief $brief): RedirectResponse
    {
        $this->authorize('publish', $brief);

        $this->briefService->publish($brief);

        return redirect()->route('dashboard')->with('success', __('Brief published successfully.'));
    }

    public function show(Brief $brief): View
    {
        $this->authorize('view', $brief);
        $brief->load(['team', 'author']);

        return view('briefs.show', compact('brief'));
    }
}
