@extends('layouts.app')

@section('title', __('My Daily Update'))
@section('breadcrumb', __('Daily report'))

@section('content')
@php
    $selectedType = old('blocker_type', $update?->blockerTypeForForm() ?? 'none');
    $selectedBlockerId = old('blocker_id', $update?->blocker_id);
@endphp

<p class="text-muted mb-4">{{ today()->translatedFormat('l j F Y') }}</p>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ $update ? route('daily-update.update') : route('daily-update.store') }}">
            @csrf
            @if($update)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="done" class="form-label fw-semibold text-success">{{ __('Done') }}</label>
                <textarea name="done" id="done" rows="4"
                          class="form-control @error('done') is-invalid @enderror"
                          required>{{ old('done', $update?->done()) }}</textarea>
                @error('done')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="in_progress" class="form-label fw-semibold text-muted">{{ __('In Progress') }}</label>
                <textarea name="in_progress" id="in_progress" rows="4"
                          class="form-control @error('in_progress') is-invalid @enderror"
                          required>{{ old('in_progress', $update?->inProgress()) }}</textarea>
                @error('in_progress')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-danger">{{ __('Blocker') }}</label>

                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="form-check">
                        <input class="form-check-input blocker-type" type="radio" name="blocker_type" id="blocker_type_none" value="none"
                               @checked($selectedType === 'none')>
                        <label class="form-check-label" for="blocker_type_none">{{ __('No blocker') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input blocker-type" type="radio" name="blocker_type" id="blocker_type_existing" value="existing"
                               @checked($selectedType === 'existing')>
                        <label class="form-check-label" for="blocker_type_existing">{{ __('Select existing blocker') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input blocker-type" type="radio" name="blocker_type" id="blocker_type_new" value="new"
                               @checked($selectedType === 'new')>
                        <label class="form-check-label" for="blocker_type_new">{{ __('Add new blocker') }}</label>
                    </div>
                </div>

                <div id="existing-blocker-field" class="mb-2">
                    <select name="blocker_id" id="blocker_id" class="form-select @error('blocker_id') is-invalid @enderror">
                        <option value="">{{ __('Choose a blocker') }}</option>
                        @foreach($teamBlockers as $teamBlocker)
                            <option value="{{ $teamBlocker->id }}" @selected((string) $selectedBlockerId === (string) $teamBlocker->id)>
                                {{ $teamBlocker->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('blocker_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div id="new-blocker-field">
                    <input type="text" name="new_blocker" id="new_blocker" value="{{ old('new_blocker') }}"
                           class="form-control @error('new_blocker') is-invalid @enderror"
                           placeholder="{{ __('Describe the new blocker') }}">
                    @error('new_blocker')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @error('blocker_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('Status') }}</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach(\App\Enums\UpdateStatus::cases() as $status)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_{{ $status->value }}"
                                   value="{{ $status->value }}"
                                   {{ old('status', $update?->status?->value ?? \App\Enums\UpdateStatus::Green->value) === $status->value ? 'checked' : '' }}
                                   required>
                            <label class="form-check-label" for="status_{{ $status->value }}">
                                {{ $status->emoji() }} {{ $status->label() }}
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $update ? __('Update') : __('Submit') }}</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const blockerTypes = document.querySelectorAll('.blocker-type');
    const existingField = document.getElementById('existing-blocker-field');
    const newField = document.getElementById('new-blocker-field');
    const blockerSelect = document.getElementById('blocker_id');
    const newBlockerInput = document.getElementById('new_blocker');

    function syncBlockerFields() {
        const selected = document.querySelector('.blocker-type:checked')?.value ?? 'none';

        existingField.style.display = selected === 'existing' ? 'block' : 'none';
        newField.style.display = selected === 'new' ? 'block' : 'none';

        blockerSelect.disabled = selected !== 'existing';
        newBlockerInput.disabled = selected !== 'new';

        if (selected !== 'existing') {
            blockerSelect.value = '';
        }

        if (selected !== 'new') {
            newBlockerInput.value = '';
        }
    }

    blockerTypes.forEach((input) => input.addEventListener('change', syncBlockerFields));
    syncBlockerFields();
</script>
@endpush
@endsection
