{{ __('Brief du jour') }} — {{ $brief->team->name }}
{{ $brief->date->translatedFormat('l j F Y') }}
@if($brief->isPublished())
{{ __('Published by :name on :date', [
    'name' => $brief->author->name,
    'date' => $brief->published_at?->translatedFormat('j F Y H:i'),
]) }}
@endif

{{ __('Done') }}:
{{ $brief->done() ?: '—' }}

{{ __('In Progress') }}:
{{ $brief->inProgress() ?: '—' }}

{{ __('Blocker') }}:
{{ $brief->blocker() ?: '—' }}
