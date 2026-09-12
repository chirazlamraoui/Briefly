@include('partials.searchable-multi-select', [
    'name' => 'team_ids[]',
    'field' => 'team_ids',
    'inputId' => 'user_teams_picker',
    'options' => $teams,
    'selected' => $selectedTeamIds,
    'tableType' => 'user_teams',
    'optionLabel' => 'name',
    'teamLeadField' => 'team_lead_ids[]',
    'teamLeadSelected' => $selectedTeamLeadIds,
    'placeholder' => __('Search teams to add...'),
    'empty' => __('No teams found.'),
    'listEmpty' => __('No teams assigned yet.'),
])
