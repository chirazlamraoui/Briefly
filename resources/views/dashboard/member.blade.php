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

<div class="row g-4">
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

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('My Tasks') }}</span>
        <a href="{{ route('tasks.my') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
    </div>
    <div class="card-body">
        @forelse($assignedTasks->take(5) as $task)
            <a href="{{ route('tasks.show', $task) }}" class="d-flex justify-content-between align-items-start mb-3 text-decoration-none text-body">
                <div class="small">
                    <div class="fw-semibold">{{ $task->title }}</div>
                    <div class="text-muted">{{ $task->project->name }}</div>
                </div>
                @include('partials.status-pill', ['status' => $task->status])
            </a>
        @empty
            <div class="text-center py-3 text-muted small">{{ __('No tasks assigned to you yet.') }}</div>
        @endforelse
    </div>
</div>
@endsection
