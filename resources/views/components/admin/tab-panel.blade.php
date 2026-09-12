@props([
    'slug',
    'active' => false,
])

<div @class(['tab-pane fade', 'show active' => $active])
     id="{{ $slug }}-panel"
     role="tabpanel"
     aria-labelledby="{{ $slug }}-tab"
     tabindex="0">
    {{ $slot }}
</div>
