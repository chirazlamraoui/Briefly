@extends('layouts.app')

@section('title', __('New user'))
@section('breadcrumb', __('User assignments'))

@section('content')
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
                <label for="team_id" class="form-label fw-semibold">{{ __('Team') }}</label>
                <select name="team_id" id="team_id" class="form-select @error('team_id') is-invalid @enderror" required>
                    <option value="">{{ __('Choose a team') }}</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) old('team_id') === (string) $team->id)>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
                @error('team_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
