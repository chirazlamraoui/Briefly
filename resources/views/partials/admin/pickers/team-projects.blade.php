@include('partials.searchable-multi-select', [
    'name' => 'project_ids[]',
    'field' => 'project_ids',
    'inputId' => 'team_projects_picker',
    'options' => $projects,
    'selected' => $selectedProjectIds,
    'tableType' => 'projects',
    'optionLabel' => 'name',
    'optionMeta' => 'description',
    'placeholder' => __('Search projects to add...'),
    'empty' => __('No projects found.'),
    'listEmpty' => __('No projects assigned yet.'),
])
