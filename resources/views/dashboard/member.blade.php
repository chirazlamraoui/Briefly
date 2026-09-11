@extends('layouts.app')

@section('title', __('Dashboard'))
@section('breadcrumb', __('Overview'))

@section('content')
@include('partials.update-reminder')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">{{ __('Welcome back, :name', ['name' => $user->name]) }}</p>
        <p class="mb-0 small">{{ $today->translatedFormat('l j F Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('tasks.my') }}" class="btn btn-outline-secondary btn-sm">{{ __('My Tasks') }}</a>
        <a href="{{ route('daily-update.history') }}" class="btn btn-outline-secondary btn-sm">{{ __('My update history') }}</a>
        <a href="{{ route('daily-update.edit') }}" class="btn btn-primary">
            {{ $todayUpdate ? __('Edit My Update') : __('Submit Daily Update') }}
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">{{ __('My Daily Update') }}</div>
            <div class="card-body">
                @if($todayUpdate)
                    <p class="mb-3">
                        @include('partials.status-pill', ['status' => $todayUpdate->status])
                    </p>
                    <h6 class="text-success">{{ __('Done') }}</h6>
                    <p class="small">{{ $todayUpdate->done() ?: '—' }}</p>
                    @if($todayUpdate->task)
                        <p class="small text-muted mb-3">
                            {{ __('Project') }}: {{ $todayUpdate->task->project->name }}
                            · {{ __('Task') }}: {{ $todayUpdate->task->title }}
                        </p>
                    @endif
                    <h6 class="text-muted">{{ __('In Progress') }}</h6>
                    <p class="small">{{ $todayUpdate->inProgress() ?: '—' }}</p>
                    <h6 class="text-danger">{{ __('Blocker') }}</h6>
                    <p class="small mb-0">{{ $todayUpdate->blockerLabel() ?: '—' }}</p>
                @else
                    <div class="text-center py-4 text-muted">
                        {{ __('You haven\'t submitted your daily update yet.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Today\'s Brief') }}</div>
            <div class="card-body">
                @if($publishedBrief)
                    <p class="text-muted small mb-3">
                        {{ __('Published') }} {{ $publishedBrief->published_at?->translatedFormat('j F Y H:i') }}
                    </p>
                    <a href="{{ route('briefs.show', $publishedBrief) }}" class="btn btn-outline-primary btn-sm">
                        {{ __('Read today\'s brief') }}
                    </a>
                @else
                    <div class="text-center py-4 text-muted">
                        {{ __('No brief published for today yet.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card">
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
    </div>
</div>
@endsection
