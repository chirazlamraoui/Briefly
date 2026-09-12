@extends('layouts.app')

@section('title', __('Edit team'))
@section('breadcrumb', $team->name)

@section('content')

<x-admin.tabbed-form
    :action="route('admin.teams.update', $team)"
    method="PUT"
    tabs-id="teamEdit"
    :cancel-url="route('admin.teams.index')"
    :submit-label="__('Save team')"
    :tabs="[
        ['slug' => 'team-info', 'label' => __('Team info')],
        ['slug' => 'team-members', 'label' => __('Team members')],
        ['slug' => 'team-projects', 'label' => __('Projects')],
    ]"
>
    <x-admin.tab-panel slug="team-info" :active="true">
        @include('partials.admin.fields.text', [
            'name' => 'name',
            'label' => __('Team name'),
            'value' => old('name', $team->name),
            'required' => true,
            'autofocus' => true,
            'margin' => 'mb-0',
            'help' => __('Manage the team lead and members on the Team members tab.'),
        ])
    </x-admin.tab-panel>

    <x-admin.tab-panel slug="team-members">
        @include('partials.admin.pickers.team-members', [
            'users' => $users,
            'selectedUserIds' => $selectedUserIds,
            'teamLeadSelected' => old('team_lead_id', $selectedTeamLeadId),
        ])
    </x-admin.tab-panel>

    <x-admin.tab-panel slug="team-projects">
        @include('partials.admin.pickers.team-projects', [
            'projects' => $projects,
            'selectedProjectIds' => $selectedProjectIds,
        ])
    </x-admin.tab-panel>
</x-admin.tabbed-form>

@endsection
