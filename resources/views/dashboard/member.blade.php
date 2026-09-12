@extends('layouts.app')

@section('title', __('Dashboard'))
@section('breadcrumb', __('Overview'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">{{ __('Welcome back, :name', ['name' => $user->name]) }}</p>
        <p class="mb-0 small">{{ $today->translatedFormat('l j F Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('tasks.my') }}" class="btn btn-primary btn-sm">{{ __('My Tasks') }}</a>
        <a href="{{ route('tasks.history') }}" class="btn btn-outline-secondary btn-sm">{{ __('Task history') }}</a>
    </div>
</div>

@include('partials.completion-summary-card', [
    'rate' => $progress['overall_rate'],
    'done' => $progress['done_count'],
    'total' => $progress['total_count'],
    'label' => __('My completion rate'),
])

<div class="row g-4 mb-4">
    @php
        $activeTasks = $assignedTasks->whereIn('status', [\App\Enums\TaskStatus::Todo, \App\Enums\TaskStatus::InProgress, \App\Enums\TaskStatus::Blocked]);
        $blockedTasks = $assignedTasks->where('status', \App\Enums\TaskStatus::Blocked);
    @endphp

    <div class="col-md-4">
        <div class="stat-card text-center p-4 h-100">
            <div class="display-6 fw-bold">{{ $activeTasks->count() }}</div>
            <div class="text-muted small">{{ __('Active tasks') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center p-4 h-100">
            <div class="display-6 fw-bold text-danger">{{ $blockedTasks->count() }}</div>
            <div class="text-muted small">{{ __('Blocked') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center p-4 h-100">
            <div class="display-6 fw-bold text-success">{{ $assignedTasks->where('status', \App\Enums\TaskStatus::Done)->count() }}</div>
            <div class="text-muted small">{{ __('Done') }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('Task progress') }}</span>
        <a href="{{ route('tasks.my') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
    </div>
    <div class="card-body">
        @forelse($progress['tasks'] as $entry)
            @php($task = $entry['task'])
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
@endsection
