@extends('layouts.app')

@section('title', __('Tasks'))
@section('breadcrumb', __('Assigned tasks'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Your assigned tasks across all teams.') }}</p>
    <a href="{{ route('tasks.history') }}" class="btn btn-outline-secondary btn-sm">{{ __('Task history') }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Task') }}</th>
                        <th>{{ __('Team') }}</th>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Updated') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-body fw-semibold">
                                    {{ $task->title }}
                                </a>
                                @if($task->blocker_note)
                                    <div class="small text-danger">{{ Str::limit($task->blocker_note, 80) }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $taskService->teamLabelForTask($task, $user) }}</td>
                            <td class="small">{{ $task->project->name }}</td>
                            <td>@include('partials.status-pill', ['status' => $task->status])</td>
                            <td class="small text-muted">{{ $task->updated_at->translatedFormat('j M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ __('No tasks assigned to you yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
