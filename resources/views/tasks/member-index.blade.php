@extends('layouts.app')

@section('title', __('My Tasks'))
@section('breadcrumb', __('Assigned tasks'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('My Tasks') }}</span>
        <a href="{{ route('daily-update.edit') }}" class="btn btn-sm btn-outline-primary">{{ __('My Daily Update') }}</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Task') }}</th>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Task status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $task->title }}</div>
                                @if($task->description)
                                    <div class="small text-muted">{{ Str::limit($task->description, 80) }}</div>
                                @endif
                            </td>
                            <td>{{ $task->project->name }}</td>
                            <td>
                                <span class="badge bg-{{ $task->status->badgeClass() }} status-badge">
                                    {{ $task->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('No tasks assigned to you yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
