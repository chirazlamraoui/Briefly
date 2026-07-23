<?php

namespace App\Http\Controllers;

use App\Enums\BriefStatus;
use App\Enums\UserRole;
use App\Models\Brief;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $teamId = auth()->user()->team_id;

        $query = Brief::query()
            ->with('author')
            ->where('team_id', $teamId)
            ->where('status', BriefStatus::Published)
            ->orderByDesc('date');

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        if ($request->filled('date') && ! $request->filled('date_from') && ! $request->filled('date_to')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('author_id')) {
            $query->where('created_by', $request->input('author_id'));
        }

        if ($request->filled('q')) {
            $keyword = $request->input('q');
            $section = $request->input('search_in', 'all');

            $query->where(function ($builder) use ($keyword, $section) {
                match ($section) {
                    'done' => $builder->where('content->done', 'like', "%{$keyword}%"),
                    'in_progress' => $builder->where('content->in_progress', 'like', "%{$keyword}%"),
                    'blocker' => $builder->where('content->blocker', 'like', "%{$keyword}%"),
                    default => $builder->where('content->done', 'like', "%{$keyword}%")
                        ->orWhere('content->in_progress', 'like', "%{$keyword}%")
                        ->orWhere('content->blocker', 'like', "%{$keyword}%"),
                };
            });
        }

        $briefs = $query->paginate(10)->withQueryString();

        $authors = User::query()
            ->where('team_id', $teamId)
            ->where('role', UserRole::TeamLead)
            ->whereIn('id', Brief::query()
                ->where('team_id', $teamId)
                ->where('status', BriefStatus::Published)
                ->select('created_by'))
            ->orderBy('name')
            ->get();

        return view('history.index', compact('briefs', 'authors'));
    }

    public function show(Brief $brief): View
    {
        $this->authorize('view', $brief);
        $brief->load(['team', 'author']);

        return view('history.show', compact('brief'));
    }
}
