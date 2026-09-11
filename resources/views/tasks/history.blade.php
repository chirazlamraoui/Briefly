@extends('layouts.app')

@section('title', __('Task history'))
@section('breadcrumb', __('My Tasks'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('All progress updates you have saved on your tasks.') }}</p>
    <a href="{{ route('tasks.my') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to tasks') }}</a>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($updates as $update)
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                    <div>
                        <strong>{{ $update->created_at->translatedFormat('l j F Y H:i') }}</strong>
                        <div class="small mt-1">
                            <a href="{{ route('tasks.show', $update->task) }}" class="text-decoration-none">{{ $update->task->title }}</a>
                            <span class="text-muted">· {{ $update->task->project->name }}</span>
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
            <div class="list-group-item text-muted text-center py-5">{{ __('No progress history yet.') }}</div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $updates->links() }}</div>
@endsection
