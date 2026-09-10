@extends('layouts.app')

@section('title', __('Administration'))
@section('breadcrumb', __('Global overview'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Overview of all teams, projects and tasks.') }}</p>
    <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-primary">{{ __('Manage project teams') }}</a>
    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">{{ __('User assignments') }}</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card text-center p-4">
            <div class="display-6 fw-bold">{{ $stats['team_count'] }}</div>
            <div class="text-muted small">{{ __('Teams') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center p-4">
            <div class="display-6 fw-bold">{{ $stats['project_count'] }}</div>
            <div class="text-muted small">{{ __('Projects') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center p-4">
            <div class="display-6 fw-bold">{{ $stats['task_count'] }}</div>
            <div class="text-muted small">{{ __('Tasks') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card p-4 small">
            <div class="mb-1">{{ __('To Do') }}: {{ $stats['task_status_counts']['TODO'] }}</div>
            <div class="mb-1">{{ __('In Progress') }}: {{ $stats['task_status_counts']['IN_PROGRESS'] }}</div>
            <div>{{ __('Done') }}: {{ $stats['task_status_counts']['DONE'] }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Teams') }}</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Team') }}</th>
                                <th>{{ __('Team Lead') }}</th>
                                <th>{{ __('Members') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teams as $team)
                                <tr>
                                    <td class="fw-semibold">{{ $team->name }}</td>
                                    <td class="small">{{ $team->teamLead?->name ?? '—' }}</td>
                                    <td>{{ $team->member_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">{{ __('No teams found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('Projects') }}</span>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Manage links') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Project') }}</th>
                                <th>{{ __('Teams') }}</th>
                                <th>{{ __('Tasks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $project->name }}</div>
                                        @if($project->description)
                                            <div class="small text-muted">{{ Str::limit($project->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $project->teams->pluck('name')->join(', ') ?: '—' }}</td>
                                    <td>{{ $project->tasks_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">{{ __('No projects found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
