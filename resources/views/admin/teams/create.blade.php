@extends('layouts.app')

@section('title', __('New team'))
@section('breadcrumb', __('Teams'))

@section('content')
@include('admin.partials.nav')

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.teams.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">{{ __('Team name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="team_lead_id" class="form-label fw-semibold">{{ __('Team Lead') }}</label>
                <select name="team_lead_id" id="team_lead_id" class="form-select @error('team_lead_id') is-invalid @enderror">
                    <option value="">{{ __('Choose a team lead') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('team_lead_id') === (string) $user->id)>
                            {{ $user->name }}@if($user->job_title) · {{ $user->job_title }}@endif
                        </option>
                    @endforeach
                </select>
                @error('team_lead_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('Members') }}</label>
                <div class="row g-2">
                    @foreach($users as $user)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="user_ids[]" id="user_{{ $user->id }}"
                                       value="{{ $user->id }}"
                                       @checked(in_array($user->id, old('user_ids', []), true))>
                                <label class="form-check-label" for="user_{{ $user->id }}">
                                    @include('admin.partials.user-picker-label', ['user' => $user])
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('user_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Create team') }}</button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
