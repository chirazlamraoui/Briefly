@extends('layouts.app')

@section('title', __('Member profile'))
@section('breadcrumb', __('Team monitoring'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $user->name }}</h2>
        <p class="text-muted small mb-0">{{ $user->role->label() }} · {{ $user->email }}</p>
    </div>
    <a href="{{ route('team.updates') }}" class="btn btn-outline-secondary btn-sm">{{ __('Team Updates') }}</a>
</div>

<div class="card">
    <div class="card-header">{{ __('Update history') }}</div>
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
            <div class="list-group-item text-muted text-center py-5">{{ __('No updates found for this member.') }}</div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $updates->links() }}</div>
@endsection
