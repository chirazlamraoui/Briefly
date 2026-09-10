@extends('layouts.app')

@section('title', __('Edit user assignment'))
@section('breadcrumb', $user->name)

@section('content')
<div class="card">
    <div class="card-body p-4">
        <p class="text-muted mb-4">{{ $user->email }}</p>

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="team_id" class="form-label fw-semibold">{{ __('Team') }}</label>
                <select name="team_id" id="team_id" class="form-select @error('team_id') is-invalid @enderror" required>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) old('team_id', $user->team_id) === (string) $team->id)>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
                @error('team_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Save assignment') }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
