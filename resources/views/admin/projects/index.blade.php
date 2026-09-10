@extends('layouts.app')

@section('title', __('Project teams'))
@section('breadcrumb', __('Administration'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Choose which teams participate in each project.') }}</p>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">{{ __('Back to overview') }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Teams') }}</th>
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
                            <td class="small">{{ $project->teams->pluck('name')->join(', ') ?: '—' }}</td>
                            <td>{{ $project->tasks_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('Manage teams') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No projects found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
