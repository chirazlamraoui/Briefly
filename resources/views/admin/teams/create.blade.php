@extends('layouts.app')

@section('title', __('New team'))
@section('breadcrumb', __('Teams'))

@section('content')
@include('admin.partials.nav')

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.teams.store') }}">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label fw-semibold">{{ __('Team name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Create team') }}</button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
