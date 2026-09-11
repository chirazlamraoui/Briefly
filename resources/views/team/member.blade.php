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
            <div class="list-group list-group-flush">
                @forelse($updates as $update)
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                            <div>
                                <strong>{{ $update->created_at->translatedFormat('l j F Y H:i') }}</strong>
                                <div class="small mt-1">
                                    <a href="{{ route('tasks.show', $update->task) }}" class="text-decoration-none">{{ $update->task->title }}</a>
                                </div>
                            </div>
                            @include('partials.status-pill', ['status' => $update->status])
                        </div>
                        <div class="small">
                            @if($update->progress_done)
                                <div class="mb-1"><span class="text-success fw-semibold">{{ __('Done so far') }}:</span> {{ $update->progress_done }}</div>
                            @endif
                            @if($update->progress_next)
                                <div class="mb-1"><span class="text-muted fw-semibold">{{ __('Still working on') }}:</span> {{ $update->progress_next }}</div>
                            @endif
                            @if($update->blocker_note)
                                <div><span class="text-danger fw-semibold">{{ __('Blocker') }}:</span> {{ $update->blocker_note }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-muted text-center py-4">{{ __('No progress history yet.') }}</div>
                @endforelse
            </div>
        </div>
        <div class="mt-3">{{ $updates->links() }}</div>
    </div>
</div>
@endsection
