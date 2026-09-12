@extends('layouts.app')

@section('title', __('Users'))
@section('breadcrumb', __('Administration'))

@section('content')

<x-admin.index-page
    :description="__('Manage users, their teams and roles.')"
    :create-url="route('admin.users.create')"
    :create-label="__('New user')"
>
    <x-admin.table :headers="[__('Name'), __('Email'), __('Teams'), __('Role'), '']">
        @forelse($users as $user)
            <tr>
                <td class="fw-semibold">{{ $user->name }}</td>
                <td class="small">{{ $user->email }}</td>
                <td class="small">{{ $user->teamsSummary() }}</td>
                <td>{{ $user->role->label() }}</td>
                <td class="text-end">
                    <x-admin.edit-link :href="route('admin.users.show', $user)" />
                </td>
            </tr>
        @empty
            <x-admin.table-empty :colspan="5" :message="__('No users found.')" />
        @endforelse
    </x-admin.table>
</x-admin.index-page>
@endsection
