@extends('layouts.app')

@section('title', __('New user'))
@section('breadcrumb', __('Users'))

@section('content')
@include('admin.partials.nav')

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">{{ __('Name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="job_title" class="form-label fw-semibold">{{ __('Job title') }}</label>
                <input type="text" name="job_title" id="job_title" value="{{ old('job_title') }}"
                       class="form-control @error('job_title') is-invalid @enderror"
                       placeholder="{{ __('e.g. Developer, QA Engineer, Marketing') }}">
                @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label fw-semibold">{{ __('Confirm password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
            </div>

            <div class="mb-3">
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

            <div class="mb-4">
                <label for="role" class="form-label fw-semibold">{{ __('Role') }}</label>
                <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                    <option value="{{ \App\Enums\UserRole::Member->value }}" @selected(old('role', \App\Enums\UserRole::Member->value) === \App\Enums\UserRole::Member->value)>
                        {{ \App\Enums\UserRole::Member->label() }}
                    </option>
                    <option value="{{ \App\Enums\UserRole::TeamLead->value }}" @selected(old('role') === \App\Enums\UserRole::TeamLead->value)>
                        {{ \App\Enums\UserRole::TeamLead->label() }}
                    </option>
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Create user') }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
