@extends('layouts.app')

@section('title', __('New user'))
@section('breadcrumb', __('Users'))

@section('content')

<x-admin.tabbed-form
    :action="route('admin.users.store')"
    tabs-id="userCreate"
    :cancel-url="route('admin.users.index')"
    :submit-label="__('Create user')"
    :tabs="[
        ['slug' => 'user-info', 'label' => __('User details')],
        ['slug' => 'user-teams', 'label' => __('Teams')],
    ]"
>
    <x-admin.tab-panel slug="user-info" :active="true">
        @include('partials.admin.user-form-fields')
    </x-admin.tab-panel>

    <x-admin.tab-panel slug="user-teams">
        @include('partials.admin.pickers.user-teams', [
            'teams' => $teams,
            'selectedTeamIds' => old('team_ids', []),
            'selectedTeamLeadIds' => old('team_lead_ids', []),
        ])
    </x-admin.tab-panel>
</x-admin.tabbed-form>

@endsection
