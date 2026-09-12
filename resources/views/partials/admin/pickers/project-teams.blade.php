@include('partials.searchable-multi-select', [
    'name' => 'team_ids[]',
    'field' => 'team_ids',
    'inputId' => 'project_teams_picker',
    'options' => $teams,
    'selected' => $selectedTeamIds,
    'tableType' => 'teams',
    'optionLabel' => 'name',
    'placeholder' => __('Search teams to add...'),
    'empty' => __('No teams found.'),
    'listEmpty' => __('No teams assigned yet.'),
])
