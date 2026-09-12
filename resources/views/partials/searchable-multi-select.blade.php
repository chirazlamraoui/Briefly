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
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.search-multi-select').forEach(function (root) {
                    if (root.dataset.initialized === 'true') {
                        return;
                    }

                    root.dataset.initialized = 'true';

                    const fieldName = root.dataset.name;
                    const tableType = root.dataset.tableType || '';
                    const emptyLabel = root.dataset.emptyLabel || 'No results found.';
                    const listEmptyLabel = root.dataset.listEmptyLabel || 'Nothing added yet.';
                    const addableRole = root.dataset.addableRole || '';
                    const removeLabel = root.dataset.removeLabel || 'Remove';
                    const teamLeadPillLabel = root.dataset.teamLeadPillLabel || 'Team Lead';
                    const memberPillLabel = root.dataset.memberPillLabel || 'Member';
                    const nameColumnLabel = root.dataset.nameColumnLabel || 'Name';
                    const jobTitleColumnLabel = root.dataset.jobTitleColumnLabel || 'Job title';
                    const teamLeadColumnLabel = root.dataset.teamLeadColumnLabel || 'Team Lead';
                    const descriptionColumnLabel = root.dataset.descriptionColumnLabel || 'Description';
                    const actionsColumnLabel = root.dataset.actionsColumnLabel || 'Actions';
                    const options = JSON.parse(root.dataset.options || '[]');
                    const input = root.querySelector('.search-multi-select__input');
                    const tableHeadEl = root.querySelector('[data-table-head]');
                    const tableBodyEl = root.querySelector('[data-table-body]');
                    const tableEmptyEl = root.querySelector('[data-table-empty]');
                    const valuesEl = root.querySelector('[data-values]');
                    const teamLeadsEl = root.querySelector('[data-team-leads]');
                    const teamLeadInput = root.querySelector('[data-team-lead]');
                    const teamLeadFieldName = root.dataset.teamLeadField || '';
                    const multipleTeamLeads = root.dataset.multipleTeamLeads === 'true';
                    const dropdown = root.querySelector('.search-multi-select__dropdown');

                    let selected = Array.from(valuesEl.querySelectorAll('input[type="hidden"]'))
                        .map((hidden) => parseInt(hidden.value, 10))
                        .filter(Number.isFinite);

                    let teamLeadIds = multipleTeamLeads && teamLeadsEl
                        ? Array.from(teamLeadsEl.querySelectorAll('input[type="hidden"]'))
                            .map((hidden) => parseInt(hidden.value, 10))
                            .filter(Number.isFinite)
                        : [];

                    let teamLeadId = !multipleTeamLeads && teamLeadInput && teamLeadInput.value !== ''
                        ? parseInt(teamLeadInput.value, 10)
                        : null;

                    function optionById(id) {
                        return options.find((option) => option.id === id);
                    }

                    function isTeamLead(id) {
                        return multipleTeamLeads ? teamLeadIds.includes(id) : teamLeadId === id;
                    }

                    function toggleTeamLead(id) {
                        if (multipleTeamLeads) {
                            if (teamLeadIds.includes(id)) {
                                teamLeadIds = teamLeadIds.filter((value) => value !== id);
                            } else {
                                teamLeadIds.push(id);
                            }
                        } else {
                            teamLeadId = teamLeadId === id ? null : id;
                        }

                        syncTeamLeadInput();
                    }

                    function syncTeamLeadInput() {
                        if (multipleTeamLeads && teamLeadsEl) {
                            teamLeadsEl.innerHTML = '';
                            teamLeadIds.forEach(function (id) {
                                const hidden = document.createElement('input');
                                hidden.type = 'hidden';
                                hidden.name = teamLeadFieldName;
                                hidden.value = String(id);
                                teamLeadsEl.appendChild(hidden);
                            });

                            return;
                        }

                        if (!teamLeadInput) {
                            return;
                        }

                        teamLeadInput.value = teamLeadId !== null ? String(teamLeadId) : '';
                    }

                    function appendLeadCell(row, id) {
                        const leadCell = document.createElement('td');
                        const isLead = isTeamLead(id);

                        const leadPill = document.createElement('button');
                        leadPill.type = 'button';
                        leadPill.className = 'search-multi-select__lead-pill' + (isLead ? ' is-active' : '');
                        leadPill.textContent = isLead ? teamLeadPillLabel : memberPillLabel;
                        leadPill.setAttribute('aria-pressed', isLead ? 'true' : 'false');
                        leadPill.addEventListener('click', function () {
                            toggleTeamLead(id);
                            renderTable();
                        });

                        leadCell.appendChild(leadPill);
                        row.appendChild(leadCell);
                    }

                    function renderTableHead() {
                        if (!tableHeadEl) {
                            return;
                        }

                        const row = document.createElement('tr');

                        if (tableType === 'members') {
                            [nameColumnLabel, jobTitleColumnLabel, teamLeadColumnLabel, actionsColumnLabel].forEach(function (label) {
                                const th = document.createElement('th');
                                th.textContent = label;
                                if (label === actionsColumnLabel) {
                                    th.className = 'text-end';
                                }
                                row.appendChild(th);
                            });
                        } else if (tableType === 'user_teams') {
                            [nameColumnLabel, teamLeadColumnLabel, actionsColumnLabel].forEach(function (label) {
                                const th = document.createElement('th');
                                th.textContent = label;
                                if (label === actionsColumnLabel) {
                                    th.className = 'text-end';
                                }
                                row.appendChild(th);
                            });
                        } else if (tableType === 'projects') {
                            [nameColumnLabel, descriptionColumnLabel, actionsColumnLabel].forEach(function (label) {
                                const th = document.createElement('th');
                                th.textContent = label;
                                if (label === actionsColumnLabel) {
                                    th.className = 'text-end';
                                }
                                row.appendChild(th);
                            });
                        } else if (tableType === 'teams') {
                            [nameColumnLabel, actionsColumnLabel].forEach(function (label) {
                                const th = document.createElement('th');
                                th.textContent = label;
                                if (label === actionsColumnLabel) {
                                    th.className = 'text-end';
                                }
                                row.appendChild(th);
                            });
                        }

                        tableHeadEl.innerHTML = '';
                        tableHeadEl.appendChild(row);
                    }

                    function renderTable() {
                        if (!tableBodyEl || !tableHeadEl) {
                            return;
                        }

                        renderTableHead();
                        tableBodyEl.innerHTML = '';

                        const sortedSelected = [...selected].sort(function (a, b) {
                            const optionA = optionById(a);
                            const optionB = optionById(b);

                            return (optionA?.label || '').localeCompare(optionB?.label || '');
                        });

                        if (sortedSelected.length === 0) {
                            tableEmptyEl?.classList.remove('d-none');
                            return;
                        }

                        tableEmptyEl?.classList.add('d-none');

                        sortedSelected.forEach(function (id) {
                            const option = optionById(id);
                            if (!option) {
                                return;
                            }

                            const row = document.createElement('tr');

                            if (tableType === 'members') {
                                const nameCell = document.createElement('td');
                                nameCell.className = 'fw-semibold';
                                nameCell.textContent = option.label;
                                row.appendChild(nameCell);

                                const jobCell = document.createElement('td');
                                jobCell.className = 'text-muted small';
                                jobCell.textContent = option.meta || '—';
                                row.appendChild(jobCell);

                                appendLeadCell(row, id);
                            } else if (tableType === 'user_teams') {
                                const nameCell = document.createElement('td');
                                nameCell.className = 'fw-semibold';
                                nameCell.textContent = option.label;
                                row.appendChild(nameCell);

                                appendLeadCell(row, id);
                            } else if (tableType === 'projects') {
                                const nameCell = document.createElement('td');
                                nameCell.className = 'fw-semibold';
                                nameCell.textContent = option.label;
                                row.appendChild(nameCell);

                                const descriptionCell = document.createElement('td');
                                descriptionCell.className = 'text-muted small';
                                descriptionCell.textContent = option.meta || '—';
                                row.appendChild(descriptionCell);
                            } else if (tableType === 'teams') {
                                const nameCell = document.createElement('td');
                                nameCell.className = 'fw-semibold';
                                nameCell.textContent = option.label;
                                row.appendChild(nameCell);
                            }

                            const actionsCell = document.createElement('td');
                            actionsCell.className = 'text-end';

                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-sm btn-outline-secondary';
                            removeBtn.textContent = removeLabel;
                            removeBtn.addEventListener('click', function () {
                                removeSelection(id);
                            });

                            actionsCell.appendChild(removeBtn);
                            row.appendChild(actionsCell);
                            tableBodyEl.appendChild(row);
                        });
                    }

                    function removeSelection(id) {
                        selected = selected.filter((value) => value !== id);

                        if (multipleTeamLeads) {
                            teamLeadIds = teamLeadIds.filter((value) => value !== id);
                        } else if (teamLeadId === id) {
                            teamLeadId = null;
                        }

                        syncTeamLeadInput();
                        syncValues();
                        renderTable();
                        renderDropdown(input.value.trim().toLowerCase());
                    }

                    function syncValues() {
                        valuesEl.innerHTML = '';
                        selected.forEach(function (id) {
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = fieldName;
                            hidden.value = String(id);
                            valuesEl.appendChild(hidden);
                        });
                    }

                    function openDropdown() {
                        dropdown.classList.remove('d-none');
                        input.setAttribute('aria-expanded', 'true');
                    }

                    function closeDropdown() {
                        dropdown.classList.add('d-none');
                        input.setAttribute('aria-expanded', 'false');
                    }

                    function isAddableOption(option) {
                        if (selected.includes(option.id)) {
                            return false;
                        }

                        if (addableRole !== '' && option.role !== addableRole) {
                            return false;
                        }

                        return true;
                    }

                    function renderDropdown(query) {
                        dropdown.innerHTML = '';
                        const normalizedQuery = query.toLowerCase();
                        const available = options.filter(function (option) {
                            if (!isAddableOption(option)) {
                                return false;
                            }

                            if (normalizedQuery === '') {
                                return true;
                            }

                            return option.label.toLowerCase().includes(normalizedQuery)
                                || (option.meta && option.meta.toLowerCase().includes(normalizedQuery));
                        });

                        if (available.length === 0) {
                            const empty = document.createElement('div');
                            empty.className = 'search-multi-select__empty';
                            empty.textContent = emptyLabel;
                            dropdown.appendChild(empty);
                            return;
                        }

                        available.forEach(function (option) {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'search-multi-select__option';
                            button.setAttribute('role', 'option');

                            const label = document.createElement('span');
                            label.textContent = option.label;
                            button.appendChild(label);

                            if (option.meta) {
                                const meta = document.createElement('small');
                                meta.className = 'd-block text-muted mt-1';
                                meta.textContent = option.meta;
                                button.appendChild(meta);
                            }

                            button.addEventListener('click', function () {
                                selected.push(option.id);
                                syncValues();
                                renderTable();
                                input.value = '';
                                renderDropdown('');
                                input.focus();
                            });
                            dropdown.appendChild(button);
                        });
                    }

                    input.addEventListener('focus', function () {
                        renderDropdown(input.value.trim().toLowerCase());
                        openDropdown();
                    });

                    input.addEventListener('input', function () {
                        renderDropdown(input.value.trim().toLowerCase());
                        openDropdown();
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeDropdown();
                            input.blur();
                        }
                    });

                    document.addEventListener('click', function (event) {
                        if (!root.contains(event.target)) {
                            closeDropdown();
                        }
                    });

                    renderTable();
                });
            });
        </script>
    @endpush
@endonce
