@extends('layouts.app')

@section('title', $project->name)
@section('breadcrumb', __('Projects'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $project->name }}</h2>
        @if($project->description)
            <p class="text-muted small mb-0">{{ $project->description }}</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to projects') }}</a>
        <a href="{{ route('tasks.create', $project) }}" class="btn btn-primary btn-sm">{{ __('New task') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-header">{{ __('Tasks') }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Task') }}</th>
                        <th>{{ __('Assignee') }}</th>
                        <th>{{ __('Task status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-body">
                                    <div class="fw-semibold">{{ $task->title }}</div>
                                    @if($task->description)
                                        <div class="small text-muted">{{ Str::limit($task->description, 80) }}</div>
                                    @endif
                                </a>
                            </td>
                            <td>{{ $task->assignee->name }}</td>
                            <td>
                                @include('partials.status-pill', ['status' => $task->status])
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No tasks found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
