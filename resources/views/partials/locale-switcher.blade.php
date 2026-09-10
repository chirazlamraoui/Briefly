@php($currentLocale = app()->getLocale())
<div class="locale-switcher" aria-label="{{ __('Language') }}">
    @foreach (['fr' => 'FR', 'en' => 'EN'] as $code => $label)
        <a href="{{ route('locale.switch', $code) }}"
           class="{{ $currentLocale === $code ? 'active' : '' }}"
           @if($currentLocale === $code) aria-current="true" @endif>
            {{ $label }}
        </a>
    @endforeach
</div>
