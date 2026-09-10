@props(['status'])

<span {{ $attributes->merge(['class' => $status->pillClass()]) }}>
    {{ $status->label() }}
</span>
