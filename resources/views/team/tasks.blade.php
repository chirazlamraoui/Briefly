@extends('layouts.app')

@section('title', __('Team Tasks'))
@section('breadcrumb', __('Team monitoring'))

@section('content')
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

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('team.tasks') }}" class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-outline-secondary' }}">{{ __('All') }}</a>
        @foreach([\App\Enums\TaskStatus::Blocked, \App\Enums\TaskStatus::InProgress, \App\Enums\TaskStatus::Todo, \App\Enums\TaskStatus::Done] as $filterStatus)
            <a href="{{ route('team.tasks', ['status' => $filterStatus->value]) }}"
               class="btn btn-sm {{ $status === $filterStatus ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $filterStatus->label() }}
            </a>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Member') }}</th>
                        <th>{{ __('Task') }}</th>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Done so far') }}</th>
                        <th>{{ __('Blocker') }}</th>
                        <th>{{ __('Completed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('team.members.show', $task->assignee) }}" class="table-link">{{ $task->assignee->name }}</a>
                            </td>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-body fw-semibold">{{ $task->title }}</a>
                            </td>
                            <td class="small">{{ $task->project->name }}</td>
                            <td>@include('partials.status-pill', ['status' => $task->status])</td>
                            <td class="small">{{ Str::limit($task->progress_done, 60) ?: '—' }}</td>
                            <td class="small text-danger">{{ Str::limit($task->blocker_note, 60) ?: '—' }}</td>
                            <td class="small">{{ $task->completed_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">{{ __('No tasks found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
