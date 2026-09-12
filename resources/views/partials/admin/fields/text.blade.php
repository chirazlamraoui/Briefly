@props([
    'name',
    'label',
    'value' => '',
    'type' => 'text',
    'required' => false,
    'autofocus' => false,
    'help' => null,
    'placeholder' => null,
    'margin' => 'mb-3',
    'disabled' => false,
])

<div class="{{ $margin }}">
    <label for="{{ $name }}" class="form-label fw-semibold">{{ $label }}</label>
    <input type="{{ $type }}"
           name="{{ $disabled ? '' : $name }}"
           id="{{ $name }}"
           value="{{ $type === 'password' ? '' : $value }}"
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           @if($required) required @endif
           @if($autofocus) autofocus @endif
           @if($disabled) disabled @endif
           class="form-control @error($name) is-invalid @enderror">
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
