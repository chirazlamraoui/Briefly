@props([
    'description',
    'createUrl',
    'createLabel',
])

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ $description }}</p>
    <a href="{{ $createUrl }}" class="btn btn-primary">{{ $createLabel }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            {{ $slot }}
        </div>
    </div>
</div>
