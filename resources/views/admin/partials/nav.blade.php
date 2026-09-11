@php
    $tabs = [
        ['route' => 'admin.dashboard', 'label' => __('Overview'), 'patterns' => ['admin.dashboard']],
        ['route' => 'admin.projects.index', 'label' => __('Projects'), 'patterns' => ['admin.projects.*']],
        ['route' => 'admin.teams.index', 'label' => __('Teams'), 'patterns' => ['admin.teams.*']],
        ['route' => 'admin.users.index', 'label' => __('Users'), 'patterns' => ['admin.users.*']],
    ];
@endphp

<div class="admin-nav mb-4">
    <div class="d-flex flex-wrap gap-2">
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="btn btn-sm {{ request()->routeIs(...$tab['patterns']) ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>
