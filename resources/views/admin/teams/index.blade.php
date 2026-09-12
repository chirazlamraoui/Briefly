@extends('layouts.app')

@section('title', __('Teams'))
@section('breadcrumb', __('Administration'))

@section('content')

<x-admin.index-page
    :description="__('Create teams and manage their members.')"
    :create-url="route('admin.teams.create')"
    :create-label="__('New team')"
>
    <x-admin.table :headers="[__('Team'), __('Team Lead'), __('Members'), '']">
        @forelse($teams as $team)
            <tr>
                <td class="fw-semibold">{{ $team->name }}</td>
                <td class="small">{{ $team->teamLead?->name ?? '—' }}</td>
                <td>{{ $team->member_count }}</td>
                <td class="text-end">
                    <x-admin.edit-link :href="route('admin.teams.edit', $team)" />
                </td>
            </tr>
        @empty
            <x-admin.table-empty :colspan="4" :message="__('No teams found.')" />
        @endforelse
    </x-admin.table>
</x-admin.index-page>
@endsection
