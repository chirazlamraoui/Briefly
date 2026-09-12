@extends('layouts.app')

@section('title', __('Overview'))
@section('breadcrumb', __('Administration'))

@section('content')
@include('admin.partials.nav')

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card admin-stat-card">
            <div class="admin-stat-card__icon"><i class="bi bi-people"></i></div>
            <div class="admin-stat-card__value">{{ $stats['team_count'] }}</div>
            <div class="admin-stat-card__label">{{ __('Teams') }}</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card admin-stat-card">
            <div class="admin-stat-card__icon"><i class="bi bi-folder2"></i></div>
            <div class="admin-stat-card__value">{{ $stats['project_count'] }}</div>
            <div class="admin-stat-card__label">{{ __('Projects') }}</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card admin-stat-card">
            <div class="admin-stat-card__icon"><i class="bi bi-check2-square"></i></div>
            <div class="admin-stat-card__value">{{ $stats['task_count'] }}</div>
            <div class="admin-stat-card__label">{{ __('Tasks') }}</div>
            <div class="admin-stat-card__meta">
                {{ __(':done of :total tasks completed', ['done' => $completion['done_count'], 'total' => $completion['total_count']]) }}
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card admin-stat-card admin-stat-card--accent">
            <div class="admin-stat-card__icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="admin-stat-card__value">{{ $completion['overall_rate'] }}%</div>
            <div class="admin-stat-card__label">{{ __('Completion rate') }}</div>
            <div class="progress completion-progress mt-3" role="progressbar"
                 aria-valuenow="{{ $completion['overall_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $completion['overall_rate'] }}%;"></div>
            </div>
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
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>{{ __('Task distribution') }}</span>
                <span class="badge text-bg-light border">{{ $completion['overall_rate'] }}% {{ __('Done') }}</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center g-4">
                    <div class="col-lg-4 d-flex justify-content-center">
                        <div class="completion-ring completion-ring--lg" style="--completion-rate: {{ $completion['overall_rate'] }};">
                            <div class="completion-ring__value">{{ $completion['overall_rate'] }}%</div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="row g-3 mb-4">
                            @foreach(\App\Enums\TaskStatus::cases() as $status)
                                <div class="col-sm-6 col-xl-3">
                                    <div class="stat-card p-3 h-100">
                                        <div class="small text-muted mb-1">{{ $status->label() }}</div>
                                        <div class="h4 mb-0 fw-bold">{{ $stats['task_status_counts'][$status->value] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div style="max-width: 420px; margin: 0 auto;">
                            <canvas id="task-status-chart" height="240" aria-label="{{ __('Task distribution') }}"></canvas>
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
