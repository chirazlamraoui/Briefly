@extends('layouts.app')

@section('title', __('Dashboard'))
@section('breadcrumb', __('Overview'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">{{ __('Welcome back, :name', ['name' => $user->name]) }}</p>
        <p class="mb-0 small">{{ $today->translatedFormat('l j F Y') }} · {{ $user->teamsSummary() }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('tasks.index') }}" class="btn btn-primary btn-sm">{{ __('Tasks') }}</a>
        @if($user->isTeamLead())
            <a href="{{ route('team.tasks') }}" class="btn btn-outline-primary btn-sm">{{ __('Team Tasks') }}</a>
        @endif
    </div>
</div>

@include('partials.completion-summary-card', [
    'rate' => $personalProgress['overall_rate'],
    'done' => $personalProgress['done_count'],
    'total' => $personalProgress['total_count'],
    'label' => __('My completion rate'),
])

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('My tasks') }}</span>
        <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
    </div>
    <div class="card-body">
        @forelse($personalProgress['tasks']->take(5) as $entry)
            @php
                $task = $entry['task'];
            @endphp
            <a href="{{ route('tasks.show', $task) }}" class="d-block mb-4 text-decoration-none text-body {{ !$loop->last ? 'pb-4 border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <div>
                        <div class="fw-semibold">{{ $task->title }}</div>
                        <div class="small text-muted">{{ $task->project->name }}</div>
                    </div>
                    @include('partials.status-pill', ['status' => $task->status])
                </div>
                <div class="d-flex justify-content-between align-items-center gap-3 mb-1">
                    <span class="small text-muted">{{ __('Progress') }}</span>
                    <span class="small fw-semibold">{{ $entry['rate'] }}%</span>
                </div>
                <div class="progress completion-progress" role="progressbar"
                     aria-valuenow="{{ $entry['rate'] }}" aria-valuemin="0" aria-valuemax="100"
                     aria-label="{{ $task->title }}">
                    <div class="progress-bar" style="width: {{ $entry['rate'] }}%;"></div>
                </div>
            </a>
        @empty
            <div class="text-center py-3 text-muted small">{{ __('No tasks assigned to you yet.') }}</div>
        @endforelse
    </div>
</div>

@foreach($ledTeams as $overview)
    @php
        $team = $overview['team'];
        $stats = $overview['stats'];
        $progress = $overview['progress'];
        $blockedTasks = $overview['blockedTasks'];
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
        <h2 class="h6 mb-0">{{ $team->name }}</h2>
        <a href="{{ route('team.tasks', ['team' => $team->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('Team Tasks') }}</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card text-center p-4">
                <div class="display-6 fw-bold">{{ $stats['total'] }}</div>
                <div class="text-muted small">{{ __('Total tasks') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center p-4">
                <div class="display-6 fw-bold text-primary">{{ $stats['in_progress'] }}</div>
                <div class="text-muted small">{{ __('In Progress') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center p-4">
                <div class="display-6 fw-bold text-danger">{{ $stats['blocked'] }}</div>
                <div class="text-muted small">{{ __('Blocked') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center p-4">
                <div class="display-6 fw-bold text-success">{{ $stats['done_this_week'] }}</div>
                <div class="text-muted small">{{ __('Done this week') }}</div>
            </div>
        </div>
    </div>

    @include('partials.completion-summary-card', [
        'rate' => $progress['overall_rate'],
        'done' => $progress['done_count'],
        'total' => $progress['total_count'],
        'label' => __('Team completion rate'),
        'showBadge' => false,
        'centered' => true,
    ])

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">{{ __('Member progress') }}</div>
                <div class="card-body">
                    @include('partials.completion-progress-list', [
                        'items' => $progress['member_rates'],
                        'empty' => __('No members in this team.'),
                        'linkRoute' => 'team.members.show',
                        'linkKey' => 'id',
                    ])
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">{{ __('Project progress') }}</div>
                <div class="card-body">
                    @include('partials.completion-progress-list', [
                        'items' => $progress['project_rates'],
                        'empty' => __('No projects assigned to this team.'),
                        'linkRoute' => 'projects.show',
                        'linkKey' => 'id',
                    ])
                </div>
            </div>
        </div>
    </div>

    @if($blockedTasks->isNotEmpty())
        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('Blocked tasks') }}</span>
                <a href="{{ route('team.tasks', ['team' => $team->id, 'status' => \App\Enums\TaskStatus::Blocked->value]) }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach($blockedTasks as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="list-group-item list-group-item-action py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold">{{ $task->title }}</div>
                                <div class="small text-muted">{{ $task->assignee->name }} · {{ $task->project->name }}</div>
                                @if($task->blocker_note)
                                    <div class="small text-danger mt-1">{{ Str::limit($task->blocker_note, 100) }}</div>
                                @endif
                            </div>
                            @include('partials.status-pill', ['status' => $task->status])
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
@endsection
