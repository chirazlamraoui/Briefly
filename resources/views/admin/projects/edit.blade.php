@extends('layouts.app')

@section('title', __('Edit project'))
@section('breadcrumb', $project->name)

@section('content')

<x-admin.tabbed-form
    :action="route('admin.projects.update', $project)"
    method="PUT"
    tabs-id="projectEdit"
    :cancel-url="route('admin.projects.index')"
    :submit-label="__('Save project')"
    :tabs="[
        ['slug' => 'project-info', 'label' => __('Project info')],
        ['slug' => 'project-teams', 'label' => __('Teams')],
    ]"
>
    <x-admin.tab-panel slug="project-info" :active="true">
        @include('partials.admin.fields.text', [
            'name' => 'name',
            'label' => __('Project name'),
            'value' => old('name', $project->name),
            'required' => true,
            'autofocus' => true,
        ])

        @include('partials.admin.fields.textarea', [
            'name' => 'description',
            'label' => __('Description'),
            'value' => old('description', $project->description),
            'help' => __('Assign teams to this project on the Teams tab.'),
        ])
    </x-admin.tab-panel>

    <x-admin.tab-panel slug="project-teams">
        @include('partials.admin.pickers.project-teams', [
            'teams' => $teams,
            'selectedTeamIds' => $selectedTeamIds,
        ])
    </x-admin.tab-panel>
</x-admin.tabbed-form>

@endsection
