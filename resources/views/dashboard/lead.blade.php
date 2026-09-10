@extends('layouts.app')

@section('title', __('Team Lead Dashboard'))
@section('breadcrumb', __('Team overview'))

@section('content')
@include('partials.update-reminder')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">{{ $user->team->name }}</p>
        <p class="mb-0 small">{{ $today->translatedFormat('l j F Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('team.updates') }}" class="btn btn-outline-primary">{{ __('Team Updates') }}</a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-primary">{{ __('Projects') }}</a>
        <a href="{{ route('briefs.today') }}" class="btn btn-primary">{{ __('Brief du jour') }}</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card text-center p-4">
            <div class="display-6 fw-bold">{{ $stats['member_count'] }}</div>
            <div class="text-muted small">{{ __('Members') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center p-4">
            <div class="display-6 fw-bold text-success">{{ $stats['updates_submitted'] }}</div>
            <div class="text-muted small">{{ __('Updates sent') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center p-4">
            <div class="display-6 fw-bold text-warning">{{ $stats['updates_missing'] }}</div>
            <div class="text-muted small">{{ __('Updates missing') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card p-4 small">
            <div class="mb-1">🟢 {{ $stats['status_counts']['GREEN'] }} {{ __('On Track') }}</div>
            <div class="mb-1">🟠 {{ $stats['status_counts']['ORANGE'] }} {{ __('Attention') }}</div>
            <div>🔴 {{ $stats['status_counts']['RED'] }} {{ __('Blocked') }}</div>
        </div>
    </div>
</div>

@if(count($blockerChart['labels']) > 0)
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('Repeated blockers') }}</span>
        <span class="small text-muted">{{ __('Last :days days', ['days' => $blockerChart['days']]) }}</span>
    </div>
    <div class="card-body">
        <canvas id="blockerChart" height="120"></canvas>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('blockerChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($blockerChart['labels']),
                datasets: [{
                    label: @json(__('Occurrences')),
                    data: @json($blockerChart['counts']),
                    backgroundColor: 'rgba(232, 180, 188, 0.85)',
                    borderColor: 'rgba(212, 145, 156, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 }
                    },
                    x: {
                        ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
                    }
                }
            }
        });
    }
</script>
@endpush
@else
<div class="card mb-4">
    <div class="card-body text-muted small">{{ __('No repeated blockers in the selected period.') }}</div>
</div>
@endif

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">{{ __('My Daily Update') }}</div>
            <div class="card-body">
                @if($todayUpdate)
                    <span class="badge bg-{{ $todayUpdate->status->badgeClass() }} status-badge">
                        {{ $todayUpdate->status->emoji() }} {{ $todayUpdate->status->label() }}
                    </span>
                    <a href="{{ route('daily-update.edit') }}" class="btn btn-sm btn-link">{{ __('Edit') }}</a>
                @else
                    <p class="text-muted">{{ __('No update submitted yet.') }}</p>
                    <a href="{{ route('daily-update.edit') }}" class="btn btn-sm btn-primary">{{ __('Submit update') }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">{{ __('Today\'s Brief') }}</div>
            <div class="card-body">
                @if($publishedBrief)
                    <span class="badge bg-success status-badge">{{ __('Published') }}</span>
                    <a href="{{ route('briefs.show', $publishedBrief) }}" class="btn btn-sm btn-outline-primary ms-2">{{ __('View') }}</a>
                @elseif($todayDraft)
                    <span class="badge bg-secondary status-badge">{{ __('Draft in progress') }}</span>
                    <a href="{{ route('briefs.today') }}" class="btn btn-sm btn-primary ms-2">{{ __('Continue editing') }}</a>
                @else
                    <p class="text-muted mb-2">{{ __('No brief started for today.') }}</p>
                    <a href="{{ route('briefs.today') }}" class="btn btn-sm btn-primary">{{ __('Prepare brief') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
