@extends('layouts.app')

@section('title', $team->name)
@section('breadcrumb', __('Teams'))

@section('content')
@include('admin.partials.nav')

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $team->name }}</h2>
        <p class="text-muted small mb-0">
            {{ __('Team Lead') }}: {{ $team->teamLead?->name ?? '—' }}
        </p>
    </div>
    <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to teams') }}</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">{{ __('Projects') }}</div>
            <div class="list-group list-group-flush">
                @forelse($team->projects as $project)
                    <div class="list-group-item py-3">
                        <div class="fw-semibold">{{ $project->name }}</div>
                        @if($project->description)
                            <div class="small text-muted mt-1">{{ Str::limit($project->description, 100) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="list-group-item text-muted text-center py-4">{{ __('No projects assigned to this team.') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">{{ __('Team members') }}</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.teams.update-users', $team) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-2 mb-4">
                        @foreach($users as $user)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="user_ids[]" id="user_{{ $user->id }}"
                                           value="{{ $user->id }}"
                                           @checked(in_array($user->id, old('user_ids', $selectedUserIds), true))>
                                    <label class="form-check-label" for="user_{{ $user->id }}">
                                        {{ $user->name }}
                                        <span class="text-muted small">· {{ $user->role->label() }}</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('user_ids')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                    <button type="submit" class="btn btn-primary">{{ __('Save members') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
