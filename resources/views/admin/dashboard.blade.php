@extends('layouts.app')

@section('title', __('Overview'))
@section('breadcrumb', __('Administration'))

@section('content')
@include('admin.partials.nav')

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
            <div class="mb-1">{{ __('Blocked') }}: {{ $stats['task_status_counts']['BLOCKED'] }}</div>
            <div>{{ __('Done') }}: {{ $stats['task_status_counts']['DONE'] }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('Teams') }}</span>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Team') }}</th>
                                <th>{{ __('Members') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teams->take(5) as $team)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('admin.teams.show', $team) }}" class="table-link">{{ $team->name }}</a>
                                    </td>
                                    <td>{{ $team->member_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">{{ __('No teams found.') }}</td>
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
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-outline-primary">{{ __('View all') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Project') }}</th>
                                <th>{{ __('Tasks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects->take(5) as $project)
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('admin.projects.edit', $project) }}" class="table-link">{{ $project->name }}</a>
                                    </td>
                                    <td>{{ $project->tasks_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">{{ __('No projects found.') }}</td>
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
