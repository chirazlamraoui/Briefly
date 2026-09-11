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
    <style>
        :root {
            --accent: #e8b4bc;
            --accent-soft: #fdf2f4;
            --accent-muted: #d4919c;
            --sidebar-width: 260px;
            --surface: #ffffff;
            --bg: #f7f7f8;
            --border: #ececef;
            --text: #18181b;
            --text-muted: #71717a;
            --bs-primary: #18181b;
            --bs-primary-rgb: 24, 24, 27;
            --bs-link-color: #18181b;
            --bs-link-hover-color: #000;
        }

        body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .btn-primary {
            --bs-btn-color: #ffffff;
            --bs-btn-bg: var(--text);
            --bs-btn-border-color: var(--text);
            --bs-btn-hover-color: #ffffff;
            --bs-btn-hover-bg: #27272a;
            --bs-btn-hover-border-color: #27272a;
            --bs-btn-active-color: #ffffff;
            --bs-btn-active-bg: #09090b;
            --bs-btn-active-border-color: #09090b;
        }

        .btn-outline-primary {
            --bs-btn-color: var(--text);
            --bs-btn-border-color: var(--border);
            --bs-btn-hover-bg: var(--accent-soft);
            --bs-btn-hover-border-color: var(--accent);
            --bs-btn-hover-color: var(--text);
            --bs-btn-active-bg: var(--accent-soft);
            --bs-btn-active-border-color: var(--accent);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(232, 180, 188, 0.2);
        }

        .app-shell {
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease;
        }

        .sidebar-brand {
            padding: 1.35rem 1.25rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
        }

        .sidebar-brand-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--accent-soft);
            color: var(--accent-muted);
            display: grid;
            place-items: center;
            font-size: 1rem;
        }

        .sidebar-nav {
            padding: 0.5rem 0.85rem;
            flex: 1;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            margin-bottom: 0.2rem;
            border-radius: 10px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.925rem;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .sidebar-link i {
            font-size: 1rem;
            width: 1.15rem;
            text-align: center;
        }

        .sidebar-link:hover {
            background: #f4f4f5;
            color: var(--text);
        }

        .sidebar-link.active {
            background: var(--accent-soft);
            color: var(--text);
            box-shadow: inset 3px 0 0 var(--accent);
        }

        .sidebar-footer {
            padding: 1rem 1.25rem 1.25rem;
            border-top: 1px solid var(--border);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0;
            margin-bottom: 0.75rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: #f4f4f5;
            color: var(--text);
            display: grid;
            place-items: center;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid var(--border);
        }

        .main-panel {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            background: var(--bg);
            border-bottom: 1px solid var(--border);
        }

        .page-content {
            padding: 0 1.5rem 2rem;
            flex: 1;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: none;
            background: var(--surface);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: var(--text);
            font-size: 0.925rem;
        }

        .stat-card {
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--surface);
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #f4f4f5;
            color: var(--text-muted);
            margin: 0 auto 0.75rem;
            font-size: 0.95rem;
        }

        .alert {
            border: none;
            border-radius: 14px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #047857;
        }

        .alert-danger {
            background: #fff1f2;
            color: #be123c;
        }

        .table {
            --bs-table-hover-bg: #fafafa;
        }

        .table thead th {
            border-bottom-color: var(--border);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .list-group-item {
            border-color: var(--border);
        }

        .list-group-item-action:hover {
            background: #fafafa;
        }

        .status-badge {
            font-size: 0.95rem;
            border-radius: 999px;
            padding: 0.45em 0.85em;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .status-pill::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-pill--green {
            background: #ecfdf5;
            color: #047857;
            border-color: #bbf7d0;
        }

        .status-pill--green::before {
            background: #10b981;
        }

        .status-pill--orange {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .status-pill--orange::before {
            background: #f59e0b;
        }

        .status-pill--red {
            background: #fff1f2;
            color: #be123c;
            border-color: #fecdd3;
        }

        .status-pill--red::before {
            background: #f43f5e;
        }

        .status-pill--blue {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .status-pill--blue::before {
            background: #3b82f6;
        }

        .status-pill--neutral {
            background: #f4f4f5;
            color: #52525b;
            border-color: #e4e4e7;
        }

        .status-pill--neutral::before {
            background: #a1a1aa;
        }

        .status-pill--muted {
            background: #fafafa;
            color: #71717a;
            border-color: #ececef;
        }

        .status-pill--muted::before {
            background: #d4d4d8;
        }

        [data-theme="dark"] .status-pill--green {
            background: #052e1c;
            color: #6ee7b7;
            border-color: #065f46;
        }

        [data-theme="dark"] .status-pill--orange {
            background: #451a03;
            color: #fcd34d;
            border-color: #92400e;
        }

        [data-theme="dark"] .status-pill--red {
            background: #4c0519;
            color: #fda4af;
            border-color: #9f1239;
        }

        [data-theme="dark"] .status-pill--blue {
            background: #172554;
            color: #93c5fd;
            border-color: #1e40af;
        }

        [data-theme="dark"] .status-pill--neutral {
            background: #27272a;
            color: #d4d4d8;
            border-color: #3f3f46;
        }

        [data-theme="dark"] .status-pill--muted {
            background: #18181b;
            color: #a1a1aa;
            border-color: #3f3f46;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-dot--green { background: #10b981; }
        .status-dot--orange { background: #f59e0b; }
        .status-dot--red { background: #f43f5e; }

        .page-title {
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .team-badge {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-weight: 500;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.25);
                z-index: 1035;
            }

            .main-panel {
                margin-left: 0;
            }
        }

        /* Guest / login */
        .guest-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            background: var(--surface);
            overflow: hidden;
        }

        .login-accent {
            height: 3px;
            background: var(--accent);
        }

        .login-header {
            padding: 2rem 2rem 0.5rem;
            text-align: center;
        }

        .login-logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--accent-soft);
            color: var(--accent-muted);
            display: grid;
            place-items: center;
            margin: 0 auto 1rem;
            font-size: 1.15rem;
        }

        .preferences-group.locale-switcher a {
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            line-height: 1;
        }

        .preferences-group.locale-switcher a:hover {
            color: var(--text);
        }

        .preferences-group.locale-switcher a.active {
            background: var(--accent-soft);
            color: var(--text);
        }

        .preferences-bar {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1060;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.2rem 0.25rem 0.2rem 0.2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .preferences-group {
            display: inline-flex;
            gap: 0.15rem;
        }

        .theme-toggle {
            border: 0;
            background: transparent;
            color: var(--text-muted);
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: grid;
            place-items: center;
            line-height: 1;
        }

        .theme-toggle:hover {
            background: var(--accent-soft);
            color: var(--text);
        }

        .alert-warning {
            background: #fffbeb;
            color: #b45309;
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --accent: #e8b4bc;
            --accent-soft: #3f2d32;
            --accent-muted: #f0c4cb;
            --surface: #141416;
            --surface-elevated: #1c1c1f;
            --bg: #09090b;
            --border: #3f3f46;
            --text: #fafafa;
            --text-muted: #d4d4d8;
            --bs-primary: #fafafa;
            --bs-primary-rgb: 250, 250, 250;
            --bs-link-color: #f0c4cb;
            --bs-link-hover-color: #fde4e8;
            --bs-body-bg: #09090b;
            --bs-body-color: #fafafa;
            --bs-border-color: #3f3f46;
            --bs-secondary-color: #d4d4d8;
        }

        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .form-label.text-muted,
        [data-theme="dark"] .small.text-muted {
            color: var(--text-muted) !important;
        }

        [data-theme="dark"] .login-card {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
        }

        [data-theme="dark"] .login-logo {
            background: var(--accent-soft);
            color: var(--accent);
        }

        [data-theme="dark"] .sidebar-link:hover,
        [data-theme="dark"] .list-group-item-action:hover {
            background: var(--surface-elevated);
        }

        [data-theme="dark"] .sidebar-link.active {
            background: var(--accent-soft);
            box-shadow: inset 3px 0 0 var(--accent);
        }

        [data-theme="dark"] .user-avatar,
        [data-theme="dark"] .stat-icon {
            background: var(--surface-elevated);
        }

        [data-theme="dark"] .table {
            --bs-table-hover-bg: var(--surface-elevated);
            --bs-table-bg: transparent;
            --bs-table-color: var(--text);
            --bs-table-border-color: var(--border);
        }

        [data-theme="dark"] .alert-success {
            background: #052e1c;
            color: #6ee7b7;
        }

        [data-theme="dark"] .alert-danger {
            background: #4c0519;
            color: #fda4af;
        }

        [data-theme="dark"] .alert-warning {
            background: #451a03;
            color: #fcd34d;
        }

        [data-theme="dark"] .alert-warning .alert-link {
            color: #fde68a;
        }

        [data-theme="dark"] .alert-danger .alert-link {
            color: #fecdd3;
        }

        [data-theme="dark"] .btn-primary {
            --bs-btn-color: #18181b;
            --bs-btn-bg: #fafafa;
            --bs-btn-border-color: #fafafa;
            --bs-btn-hover-bg: #e4e4e7;
            --bs-btn-hover-border-color: #e4e4e7;
            --bs-btn-hover-color: #18181b;
            --bs-btn-active-bg: #d4d4d8;
            --bs-btn-active-border-color: #d4d4d8;
            --bs-btn-active-color: #18181b;
        }

        [data-theme="dark"] .btn-outline-primary {
            --bs-btn-color: var(--text);
            --bs-btn-border-color: var(--border);
            --bs-btn-hover-bg: var(--accent-soft);
            --bs-btn-hover-border-color: var(--accent);
            --bs-btn-hover-color: var(--text);
        }

        [data-theme="dark"] .btn-outline-secondary {
            --bs-btn-color: var(--text-muted);
            --bs-btn-border-color: var(--border);
            --bs-btn-hover-bg: var(--surface-elevated);
            --bs-btn-hover-color: var(--text);
            --bs-btn-hover-border-color: var(--border);
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--surface-elevated);
            border-color: var(--border);
            color: var(--text);
        }

        [data-theme="dark"] .form-control::placeholder {
            color: #71717a;
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--surface-elevated);
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem rgba(232, 180, 188, 0.18);
        }

        [data-theme="dark"] .form-check-input {
            background-color: var(--surface-elevated);
            border-color: var(--border);
        }

        [data-theme="dark"] .form-check-input:checked {
            background-color: var(--accent-muted);
            border-color: var(--accent-muted);
        }

        [data-theme="dark"] .form-check-label {
            color: var(--text);
        }

        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-theme="dark"] .preferences-bar {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
        }

        [data-theme="dark"] .pagination .page-link {
            background-color: var(--surface);
            border-color: var(--border);
            color: var(--text-muted);
        }

        [data-theme="dark"] .pagination .page-item.active .page-link {
            background-color: var(--accent-soft);
            border-color: var(--accent);
            color: var(--text);
        }

        [data-theme="dark"] .sidebar-overlay.show {
            background: rgba(0, 0, 0, 0.55);
        }
    </style>
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
                {{ __('Administration') }}
            </a>
            <a href="{{ route('admin.projects.index') }}" class="sidebar-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i>
                {{ __('Project teams') }}
            </a>
            <a href="{{ route('admin.teams.index') }}" class="sidebar-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i>
                {{ __('Team management') }}
            </a>
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                {{ __('User assignments') }}
            </a>
            @else
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i>
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('daily-update.edit') }}" class="sidebar-link {{ request()->routeIs('daily-update.edit') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                {{ __('My Daily Update') }}
            </a>
            <a href="{{ route('daily-update.history') }}" class="sidebar-link {{ request()->routeIs('daily-update.history') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark"></i>
                {{ __('My update history') }}
            </a>
            <a href="{{ route('tasks.my') }}" class="sidebar-link {{ request()->routeIs('tasks.my') ? 'active' : '' }}">
                <i class="bi bi-check2-square"></i>
                {{ __('My Tasks') }}
            </a>
            @if(auth()->user()->isTeamLead())
            <a href="{{ route('projects.index') }}" class="sidebar-link {{ request()->routeIs('projects.*', 'tasks.*') ? 'active' : '' }}">
                <i class="bi bi-folder2"></i>
                {{ __('Projects') }}
            </a>
            <a href="{{ route('team.updates') }}" class="sidebar-link {{ request()->routeIs('team.updates', 'team.members.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                {{ __('Team Updates') }}
            </a>
            <a href="{{ route('briefs.today') }}" class="sidebar-link {{ request()->routeIs('briefs.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i>
                {{ __('Brief du jour') }}
            </a>
            @endif
            <a href="{{ route('history.index') }}" class="sidebar-link {{ request()->routeIs('history.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                {{ __('Historique') }}
            </a>
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
                    <div class="small text-muted">{{ auth()->user()->role->label() }}</div>
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
            <span class="badge rounded-pill team-badge">
                <i class="bi bi-building me-1"></i>
                @if(auth()->user()->isAdmin())
                    {{ __('All teams') }}
                @else
                    {{ auth()->user()->team->name }}
                @endif
            </span>
        </header>

        <main class="page-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @else
    <div class="guest-shell">
        @yield('content')
    </div>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const root = document.documentElement;
            const toggle = document.getElementById('themeToggle');
            const icons = document.querySelectorAll('[data-theme-icon]');

            function syncThemeIcon(theme) {
                icons.forEach((icon) => {
                    icon.classList.toggle('d-none', icon.dataset.themeIcon !== theme);
                });
            }

            function setTheme(theme) {
                root.setAttribute('data-theme', theme);
                localStorage.setItem('briefly-theme', theme);
                syncThemeIcon(theme === 'dark' ? 'light' : 'dark');
            }

            syncThemeIcon(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');

            toggle?.addEventListener('click', () => {
                const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                setTheme(next);
            });
        })();
    </script>
    @stack('scripts')
    @auth
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        function closeSidebar() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }

        toggle?.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        overlay?.addEventListener('click', closeSidebar);
    </script>
    @endauth
</body>
</html>
