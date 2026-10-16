<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) — {{ config('app.name', 'Briefly') }}</title>
    <script>
        (function () {
            const theme = localStorage.getItem('briefly-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    @include('partials.preferences-bar')
    @auth
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <span class="sidebar-brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
            Briefly
        </a>

        <nav class="sidebar-nav">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i>
                {{ __('Overview') }}
            </a>
            <a href="{{ route('admin.projects.index') }}" class="sidebar-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                <i class="bi bi-folder2"></i>
                {{ __('Projects') }}
            </a>
            <a href="{{ route('admin.teams.index') }}" class="sidebar-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                {{ __('Teams') }}
            </a>
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                {{ __('Users') }}
            </a>
            @else
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i>
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('tasks.index') }}" class="sidebar-link {{ request()->routeIs('tasks.index', 'tasks.history', 'tasks.show', 'tasks.update-progress') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i>
                {{ __('Tasks') }}
            </a>
            @if(auth()->user()->isTeamLead())
            <a href="{{ route('team.tasks') }}" class="sidebar-link {{ request()->routeIs('team.tasks', 'team.members.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                {{ __('Team Tasks') }}
            </a>
            <a href="{{ route('projects.index') }}" class="sidebar-link {{ request()->routeIs('projects.*', 'tasks.create', 'tasks.store', 'tasks.edit', 'tasks.update') ? 'active' : '' }}">
                <i class="bi bi-folder2"></i>
                {{ __('Projects') }}
            </a>
            @endif
            @endif
            <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-person"></i>
                {{ __('Profile') }}
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="overflow-hidden">
                    <div class="fw-semibold text-truncate">{{ auth()->user()->name }}</div>
                    <div class="small text-muted text-truncate">
                        @if(auth()->user()->isAdmin())
                            {{ auth()->user()->role->label() }}
                        @else
                            {{ auth()->user()->teamsSummary() }}
                        @endif
                    </div>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100 btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> {{ __('Logout') }}
                </button>
            </form>
        </div>
    </aside>

    <div class="main-panel">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-primary d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="small text-muted">@yield('breadcrumb', __('Briefly'))</div>
                    <h1 class="h5 page-title mb-0">@yield('title', __('Dashboard'))</h1>
                </div>
            </div>
        </header>

        <main class="page-content">
            @yield('content')
        </main>
    </div>
    @else
    <div class="guest-shell">
        @yield('content')
    </div>
    @endauth

    @if(session('success') || session('error'))
        <div class="toast-container briefly-toast-container">
            @if(session('success'))
                <div class="toast briefly-flash-toast alert alert-success border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex align-items-center">
                        <div class="toast-body d-flex align-items-center py-2">
                            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="toast briefly-flash-toast alert alert-danger border-0 mb-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex align-items-center">
                        <div class="toast-body d-flex align-items-center py-2">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
