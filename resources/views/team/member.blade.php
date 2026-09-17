@extends('layouts.app')

@section('title', $user->name)
@section('breadcrumb', __('Team monitoring'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $user->name }}</h2>
        <p class="text-muted small mb-0">
            {{ $user->email }}
            @if($user->job_title)
                · {{ $user->job_title }}
            @endif
        </p>
    </div>
    <a href="{{ route('team.tasks') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to team tasks') }}</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">{{ __('Assigned tasks') }}</div>
            <div class="list-group list-group-flush">
                @forelse($tasks as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="list-group-item list-group-item-action py-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">{{ $task->title }}</div>
                                <div class="small text-muted">{{ $task->project->name }}</div>
                            </div>
                            @include('partials.status-pill', ['status' => $task->status])
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-muted text-center py-4">{{ __('No tasks assigned.') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">{{ __('Task history') }}</div>
            @include('partials.task-update-list', ['updates' => $updates])
        </div>
        <div class="mt-3">{{ $updates->links() }}</div>
    </div>
</div>
@endsection
