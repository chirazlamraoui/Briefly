@extends('layouts.app')

@section('title', __('New project'))
@section('breadcrumb', __('Projects'))

@section('content')
@include('admin.partials.nav')

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.projects.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">{{ __('Project name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                <textarea name="description" id="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('Teams') }}</label>
                <div class="row g-2">
                    @foreach($teams as $team)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="team_ids[]" id="team_{{ $team->id }}"
                                       value="{{ $team->id }}"
                                       @checked(in_array($team->id, old('team_ids', []), true))>
                                <label class="form-check-label" for="team_{{ $team->id }}">{{ $team->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('team_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Create project') }}</button>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
