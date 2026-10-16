@props([
    'name' => 'team_ids[]',
    'field' => 'team_ids',
    'inputId' => 'team_picker',
    'label' => null,
    'options' => collect(),
    'selected' => [],
    'placeholder' => __('Search teams...'),
    'empty' => __('No teams found.'),
    'listEmpty' => __('Nothing added yet.'),
    'tableType' => null,
    'optionLabel' => 'name',
    'optionMeta' => null,
    'optionRole' => null,
    'addableRole' => null,
    'teamLeadField' => null,
    'teamLeadSelected' => null,
])

@php
    $selectedIds = collect(old($field, $selected))
        ->filter(fn ($id) => $id !== null && $id !== '')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    $optionItems = $options->map(function ($option) use ($optionLabel, $optionMeta, $optionRole) {
        $item = [
            'id' => (int) $option->id,
            'label' => (string) data_get($option, $optionLabel),
            'meta' => $optionMeta ? (string) (data_get($option, $optionMeta) ?? '') : '',
        ];

        if ($optionRole) {
            $role = data_get($option, $optionRole);
            $item['role'] = $role instanceof \BackedEnum ? $role->value : (string) $role;
        }

        return $item;
    })->values();

    $multipleTeamLeads = $teamLeadField !== null && str_ends_with($teamLeadField, '[]');
    $teamLeadFieldKey = $multipleTeamLeads ? rtrim($teamLeadField, '[]') : $teamLeadField;

    if ($multipleTeamLeads) {
        $teamLeadSelectedIds = collect(old($teamLeadFieldKey, $teamLeadSelected ?? []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $teamLeadValue = null;
    } else {
        $teamLeadSelectedIds = [];
        $teamLeadValue = old($teamLeadField, $teamLeadSelected);
        $teamLeadValue = $teamLeadValue !== null && $teamLeadValue !== '' ? (int) $teamLeadValue : null;
    }
@endphp

<div class="mb-0">
    @if($label)
        <label for="{{ $inputId }}_search" class="form-label fw-semibold">{{ $label }}</label>
    @endif

    <div class="search-multi-select @error($field) is-invalid @enderror"
         data-name="{{ $name }}"
         data-table-type="{{ $tableType }}"
         @if($teamLeadField) data-team-lead-field="{{ $teamLeadField }}" data-multiple-team-leads="{{ $multipleTeamLeads ? 'true' : 'false' }}" @endif
         data-options='@json($optionItems)'
         data-empty-label="{{ $empty }}"
         data-list-empty-label="{{ $listEmpty }}"
         @if($addableRole) data-addable-role="{{ $addableRole }}" @endif
         data-remove-label="{{ __('Remove') }}"
         data-team-lead-pill-label="{{ __('Team Lead') }}"
         data-member-pill-label="{{ __('Member') }}"
         data-name-column-label="{{ __('Name') }}"
         data-job-title-column-label="{{ __('Job title') }}"
         data-team-lead-column-label="{{ __('Team Lead') }}"
         data-description-column-label="{{ __('Description') }}"
         data-actions-column-label="{{ __('Actions') }}">
        <div class="search-multi-select__anchor">
            <div class="search-multi-select__control search-multi-select__control--solo">
                <input type="search"
                       id="{{ $inputId }}_search"
                       class="search-multi-select__input"
                       placeholder="{{ $placeholder }}"
                       autocomplete="off"
                       aria-controls="{{ $inputId }}_listbox"
                       aria-expanded="false"
                       role="combobox">
            </div>

            <div class="search-multi-select__dropdown d-none" id="{{ $inputId }}_listbox" role="listbox"></div>
        </div>

        <div class="search-multi-select__table-wrap">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead data-table-head></thead>
                    <tbody data-table-body></tbody>
                </table>
            </div>
            <div class="search-multi-select__table-empty d-none" data-table-empty>{{ $listEmpty }}</div>
        </div>

        <div class="search-multi-select__values" data-values>
            @foreach($selectedIds as $selectedId)
                <input type="hidden" name="{{ $name }}" value="{{ $selectedId }}">
            @endforeach
        </div>

        @if($teamLeadField)
            @if($multipleTeamLeads)
                <div data-team-leads>
                    @foreach($teamLeadSelectedIds as $leadTeamId)
                        <input type="hidden" name="{{ $teamLeadField }}" value="{{ $leadTeamId }}">
                    @endforeach
                </div>
            @else
                <input type="hidden" name="{{ $teamLeadField }}" value="{{ $teamLeadValue ?? '' }}" data-team-lead>
            @endif
        @endif
    </div>

    @error($field)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @if($teamLeadField)
        @error($teamLeadFieldKey)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @endif
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/multi-select.js') }}"></script>
    @endpush
@endonce
