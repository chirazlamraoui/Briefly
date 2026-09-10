@extends('layouts.app')

@section('title', __('My update history'))
@section('breadcrumb', __('Daily report'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('My update history') }}</span>
        <a href="{{ route('daily-update.edit') }}" class="btn btn-sm btn-outline-primary">{{ __('My Daily Update') }}</a>
    </div>
    <div class="list-group list-group-flush">
        @forelse($updates as $update)
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <strong>{{ $update->date->translatedFormat('l j F Y') }}</strong>
                    @include('partials.status-pill', ['status' => $update->status])
                </div>
                <div class="small">
                    @if($update->task)
                        <div class="mb-2">
                            <span class="text-muted fw-semibold">{{ __('Project') }}:</span> {{ $update->task->project->name }}
                            · <span class="text-muted fw-semibold">{{ __('Task') }}:</span> {{ $update->task->title }}
                        </div>
                    @endif
                    <div class="mb-1"><span class="text-success fw-semibold">{{ __('Done') }}:</span> {{ $update->done() ?: '—' }}</div>
                    <div class="mb-1"><span class="text-muted fw-semibold">{{ __('In Progress') }}:</span> {{ $update->inProgress() ?: '—' }}</div>
                    <div><span class="text-danger fw-semibold">{{ __('Blocker') }}:</span> {{ $update->blockerLabel() ?: '—' }}</div>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-5">{{ __('No updates found.') }}</div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $updates->links() }}</div>
@endsection
