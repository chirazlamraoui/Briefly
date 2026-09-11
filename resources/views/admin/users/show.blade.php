@extends('layouts.app')

@section('title', $user->name)
@section('breadcrumb', __('Users'))

@section('content')
@include('admin.partials.nav')

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $user->name }}</h2>
        <p class="text-muted small mb-0">
            {{ $user->email }}
            @if($user->job_title)
                · {{ $user->job_title }}
            @endif
            · {{ $user->role->label() }}
        </p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to users') }}</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">{{ __('Projects') }}</div>
            <div class="list-group list-group-flush">
                @forelse($user->accessibleProjects() as $project)
                    <div class="list-group-item py-3">
                        <div class="fw-semibold">{{ $project->name }}</div>
                        @if($project->description)
                            <div class="small text-muted mt-1">{{ Str::limit($project->description, 100) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="list-group-item text-muted text-center py-4">{{ __('No projects available for this user.') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">{{ __('Teams and role') }}</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Teams') }}</label>
                        <div class="row g-2">
                            @foreach($teams as $team)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="team_ids[]" id="team_{{ $team->id }}"
                                               value="{{ $team->id }}"
                                               @checked(in_array($team->id, old('team_ids', $selectedTeamIds), true))>
                                        <label class="form-check-label" for="team_{{ $team->id }}">{{ $team->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('team_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label fw-semibold">{{ __('Role') }}</label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="{{ \App\Enums\UserRole::Member->value }}" @selected(old('role', $user->role->value) === \App\Enums\UserRole::Member->value)>
                                {{ \App\Enums\UserRole::Member->label() }}
                            </option>
                            <option value="{{ \App\Enums\UserRole::TeamLead->value }}" @selected(old('role', $user->role->value) === \App\Enums\UserRole::TeamLead->value)>
                                {{ \App\Enums\UserRole::TeamLead->label() }}
                            </option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('Assigning a new team lead replaces the current lead for that team.') }}</div>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('Save assignment') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
