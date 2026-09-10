@extends('layouts.app')

@section('title', __('Projects'))
@section('breadcrumb', __('Project management'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ $team->name }}</p>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">{{ __('New project') }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Tasks') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $project->name }}</div>
                                @if($project->description)
                                    <div class="small text-muted">{{ Str::limit($project->description, 80) }}</div>
                                @endif
                            </td>
                            <td>{{ $project->tasks_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('No projects found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
