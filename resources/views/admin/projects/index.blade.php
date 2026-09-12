@extends('layouts.app')

@section('title', __('Projects'))
@section('breadcrumb', __('Administration'))

@section('content')

<x-admin.index-page
    :description="__('Manage projects and assign them to teams.')"
    :create-url="route('admin.projects.create')"
    :create-label="__('New project')"
>
    <x-admin.table :headers="[__('Project'), __('Teams'), __('Tasks'), '']">
        @forelse($projects as $project)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $project->name }}</div>
                    @if($project->description)
                        <div class="small text-muted">{{ Str::limit($project->description, 80) }}</div>
                    @endif
                </td>
                <td class="small">{{ $project->teamsSummary() }}</td>
                <td>{{ $project->tasks_count }}</td>
                <td class="text-end">
                    <x-admin.edit-link :href="route('admin.projects.edit', $project)" />
                </td>
            </tr>
        @empty
            <x-admin.table-empty :colspan="4" :message="__('No projects found.')" />
        @endforelse
    </x-admin.table>
</x-admin.index-page>
@endsection
