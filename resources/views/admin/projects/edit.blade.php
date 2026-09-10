@extends('layouts.app')

@section('title', __('Manage project teams'))
@section('breadcrumb', $project->name)

@section('content')
<div class="card">
    <div class="card-body p-4">
        <p class="text-muted mb-4">{{ __('Select the teams that participate in this project.') }}</p>

        <form method="POST" action="{{ route('admin.projects.update-teams', $project) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                @forelse($teams as $team)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="team_ids[]" id="team_{{ $team->id }}"
                               value="{{ $team->id }}"
                               @checked(in_array($team->id, old('team_ids', $selectedTeamIds), true))>
                        <label class="form-check-label" for="team_{{ $team->id }}">{{ $team->name }}</label>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ __('No teams found.') }}</p>
                @endforelse
                @error('team_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('team_ids.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Save teams') }}</button>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
