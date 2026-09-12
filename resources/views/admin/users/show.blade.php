@extends('layouts.app')

@section('title', __('Edit user'))
@section('breadcrumb', $user->name)

@section('content')

<x-admin.tabbed-form
    :action="route('admin.users.update', $user)"
    method="PUT"
    tabs-id="userEdit"
    :cancel-url="route('admin.users.index')"
    :submit-label="__('Save user')"
    :tabs="[
        ['slug' => 'user-info', 'label' => __('User details')],
        ['slug' => 'user-teams', 'label' => __('Teams')],
    ]"
>
    <x-admin.tab-panel slug="user-info" :active="true">
        @include('partials.admin.user-form-fields', ['user' => $user])
    </x-admin.tab-panel>

    <x-admin.tab-panel slug="user-teams">
        @include('partials.admin.pickers.user-teams', [
            'teams' => $teams,
            'selectedTeamIds' => $selectedTeamIds,
            'selectedTeamLeadIds' => old('team_lead_ids', $selectedTeamLeadIds),
        ])
    </x-admin.tab-panel>
</x-admin.tabbed-form>

@endsection
