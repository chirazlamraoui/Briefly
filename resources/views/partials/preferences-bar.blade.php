@php($currentLocale = app()->getLocale())
<div class="preferences-bar" aria-label="{{ __('Preferences') }}">
    <div class="preferences-group locale-switcher" aria-label="{{ __('Language') }}">
        @foreach (['fr' => 'FR', 'en' => 'EN'] as $code => $label)
            <a href="{{ route('locale.switch', $code) }}"
               class="{{ $currentLocale === $code ? 'active' : '' }}"
               @if($currentLocale === $code) aria-current="true" @endif>
                {{ $label }}
            </a>
        @endforeach
    </div>
    <button type="button" class="theme-toggle" id="themeToggle" aria-label="{{ __('Toggle dark mode') }}">
        <i class="bi bi-moon-stars" data-theme-icon="dark"></i>
        <i class="bi bi-sun d-none" data-theme-icon="light"></i>
    </button>
</div>
