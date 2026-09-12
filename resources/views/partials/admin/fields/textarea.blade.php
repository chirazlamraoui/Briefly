@props([
    'name',
    'label',
    'value' => '',
    'rows' => 3,
    'help' => null,
    'margin' => 'mb-0',
])

<div class="{{ $margin }}">
    <label for="{{ $name }}" class="form-label fw-semibold">{{ $label }}</label>
    <textarea name="{{ $name }}"
              id="{{ $name }}"
              rows="{{ $rows }}"
              class="form-control @error($name) is-invalid @enderror">{{ $value }}</textarea>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
