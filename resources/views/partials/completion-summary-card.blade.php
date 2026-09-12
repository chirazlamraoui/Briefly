@props([
    'rate' => 0,
    'done' => 0,
    'total' => 0,
    'label' => null,
    'showBadge' => true,
    'centered' => false,
])

<div class="card mb-4">
    <div class="card-header{{ $centered ? ' text-center' : ' d-flex justify-content-between align-items-center' }}">
        <span>{{ $label ?? __('Completion rate') }}</span>
        @if($showBadge)
            <span class="badge text-bg-light border">{{ $rate }}%</span>
        @endif
    </div>
    <div class="card-body d-flex align-items-center gap-4{{ $centered ? ' flex-column justify-content-center text-center' : ' flex-column flex-sm-row' }}">
        <div class="completion-ring" style="--completion-rate: {{ $rate }};">
            <div class="completion-ring__value">{{ $rate }}%</div>
        </div>
        <div>
            <p class="mb-1 fw-semibold">{{ __(':done of :total tasks completed', ['done' => $done, 'total' => $total]) }}</p>
            <p class="text-muted small mb-0">{{ __('Based on tasks marked as done.') }}</p>
        </div>
    </div>
</div>
