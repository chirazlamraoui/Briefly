@props([
    'items' => [],
    'empty' => null,
    'linkRoute' => null,
    'linkKey' => 'id',
])

@forelse($items as $item)
    <div class="mb-3 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}">
        <div class="d-flex justify-content-between align-items-center mb-1 gap-3">
            @if($linkRoute && isset($item[$linkKey]))
                <a href="{{ route($linkRoute, $item[$linkKey]) }}" class="fw-semibold text-decoration-none text-body">
                    {{ $item['name'] }}
                </a>
            @else
                <span class="fw-semibold">{{ $item['name'] }}</span>
            @endif
            <span class="small text-muted text-nowrap">{{ $item['rate'] }}%</span>
        </div>
        <div class="progress completion-progress" role="progressbar"
             aria-valuenow="{{ $item['rate'] }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="{{ $item['name'] }}">
            <div class="progress-bar" style="width: {{ $item['rate'] }}%;"></div>
        </div>
    </div>
@empty
    <p class="text-muted mb-0 text-center py-4">{{ $empty ?? __('No data yet.') }}</p>
@endforelse
