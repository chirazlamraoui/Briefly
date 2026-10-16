// Flash messages: show, then hide after 3 s.
(function () {
    document.querySelectorAll('.briefly-flash-toast').forEach(function (element) {
        const toast = bootstrap.Toast.getOrCreateInstance(element, {
            autohide: true,
            delay: 3000,
        });
        toast.show();
    });
})();

// Light / dark theme toggle, remembered in localStorage.
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

// Admin tabbed forms: open the tab named in the URL hash and keep the hash in sync.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-briefly-tabs]').forEach(function (group) {
        const hash = window.location.hash.replace('#', '');
        const tabTrigger = hash ? document.getElementById(hash + '-tab') : null;

        if (tabTrigger && group.contains(tabTrigger)) {
            bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
        }

        group.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (button) {
            button.addEventListener('shown.bs.tab', function () {
                window.location.hash = button.id.replace('-tab', '');
            });
        });
    });
});

// Mobile sidebar (only rendered for logged-in users).
(function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar) {
        return;
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }

    toggle?.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    });

    overlay?.addEventListener('click', closeSidebar);
})();
