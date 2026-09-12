@props([
    'action',
    'method' => 'POST',
    'tabsId',
    'cancelUrl',
    'submitLabel',
    'tabs' => [],
])

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <ul class="nav nav-tabs briefly-tabs mb-4" id="{{ $tabsId }}Tabs" data-briefly-tabs role="tablist">
                @foreach($tabs as $index => $tab)
                    @php
                        $slug = $tab['slug'];
                        $isActive = $tab['active'] ?? $index === 0;
                    @endphp
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if($isActive) active @endif"
                                id="{{ $slug }}-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#{{ $slug }}-panel"
                                type="button"
                                role="tab"
                                aria-controls="{{ $slug }}-panel"
                                aria-selected="{{ $isActive ? 'true' : 'false' }}">
                            {{ $tab['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="{{ $tabsId }}TabContent">
                {{ $slot }}
            </div>

            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
