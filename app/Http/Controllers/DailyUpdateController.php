<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyUpdateRequest;
use App\Models\Blocker;
use App\Models\DailyUpdate;
use App\Models\Task;
use App\Models\User;
use App\Services\BlockerService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DailyUpdateController extends Controller
{
    public function __construct(
        private BlockerService $blockerService,
        private TaskService $taskService,
    ) {}

    public function history(): View
    {
        $updates = DailyUpdate::query()
            ->with(['blocker', 'task.project'])
            ->where('user_id', auth()->id())
            ->orderByDesc('date')
            ->paginate(15);

        return view('daily-update.history', compact('updates'));
    }

    public function edit(): View
    {
        $user = auth()->user();

        $update = DailyUpdate::query()
            ->with(['blocker', 'task.project'])
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $teamBlockers = Blocker::query()
            ->where('team_id', $user->team_id)
            ->orderBy('label')
            ->get();

        $assignedTasks = $user->isMember()
            ? $this->taskService->assignedTasksFor($user)
            : collect();

        return view('daily-update.form', compact('update', 'teamBlockers', 'assignedTasks'));
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
        $validated = $request->validated();
        $blockerId = $this->blockerService->resolveBlockerId($user, $validated);
        $taskId = $this->resolveTaskId($user, $validated['task_id'] ?? null);

        DailyUpdate::create([
            'user_id' => $user->id,
            'task_id' => $taskId,
            'date' => today(),
            'content' => [
                'done' => $validated['done'],
                'in_progress' => $validated['in_progress'],
                'blocker' => '',
            ],
            'status' => $validated['status'],
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

        $user = auth()->user();
        $validated = $request->validated();
        $blockerId = $this->blockerService->resolveBlockerId($user, $validated);
        $taskId = $this->resolveTaskId($user, $validated['task_id'] ?? null);

        $update->update([
            'task_id' => $taskId,
            'content' => [
                'done' => $validated['done'],
                'in_progress' => $validated['in_progress'],
                'blocker' => '',
            ],
            'status' => $validated['status'],
            'blocker_id' => $blockerId,
        ]);

        return redirect()->route('dashboard')->with('success', __('Daily update updated.'));
    }

    private function resolveTaskId(User $user, ?int $taskId): ?int
    {
        if ($taskId === null) {
            return null;
        }

        $task = Task::query()->findOrFail($taskId);
        $this->taskService->ensureTaskAssignedToUser($task, $user);

        return $task->id;
    }
}
