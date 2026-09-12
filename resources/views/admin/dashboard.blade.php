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

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Completion rate by project') }}</div>
            <div class="card-body">
                @include('partials.completion-progress-list', [
                    'items' => $completion['project_rates'],
                    'empty' => __('No projects found.'),
                ])
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Completion rate by team') }}</div>
            <div class="card-body">
                @include('partials.completion-progress-list', [
                    'items' => $completion['team_rates'],
                    'empty' => __('No teams found.'),
                ])
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('Completion rate') }}</span>
                <span class="badge text-bg-light border">{{ $completion['overall_rate'] }}%</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-center gap-4">
                    <div class="completion-ring" style="--completion-rate: {{ $completion['overall_rate'] }};">
                        <div class="completion-ring__value">{{ $completion['overall_rate'] }}%</div>
                    </div>
                    <div class="flex-grow-1 w-100">
                        <div class="small text-muted mb-2">{{ __('Task distribution') }}</div>
                        <div style="max-width: 260px; margin: 0 auto;">
                            <canvas id="task-status-chart" height="220" aria-label="{{ __('Task distribution') }}"></canvas>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-3 text-center">
                    {{ __(':done of :total tasks completed', ['done' => $completion['done_count'], 'total' => $completion['total_count']]) }}
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">{{ __('Organization snapshot') }}</div>
            <div class="card-body d-flex flex-column justify-content-center gap-3">
                <p class="text-muted mb-0">{{ __('Overview of all teams, projects and tasks.') }}</p>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="stat-card text-center p-3">
                            <div class="h4 mb-1">{{ $stats['team_count'] }}</div>
                            <div class="small text-muted">{{ __('Teams') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="stat-card text-center p-3">
                            <div class="h4 mb-1">{{ $stats['project_count'] }}</div>
                            <div class="small text-muted">{{ __('Projects') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="stat-card text-center p-3">
                            <div class="h4 mb-1">{{ $completion['overall_rate'] }}%</div>
                            <div class="small text-muted">{{ __('Completion rate') }}</div>
                        </div>
                    </div>
                </div>
            </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('task-status-chart');

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const chartData = @json($completion['status_chart']);

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.values,
                    backgroundColor: chartData.colors,
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text') || '#18181b',
                        },
                    },
                },
            },
        });
    });
</script>
@endpush
