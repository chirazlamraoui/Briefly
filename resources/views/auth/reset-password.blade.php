@extends('layouts.app')

@section('title', __('Reset password'))

@section('content')
<div class="card login-card">
    <div class="login-accent"></div>
    <div class="login-header">
        <h1 class="h5 mb-1 fw-semibold">{{ __('Reset password') }}</h1>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-muted">{{ __('Email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email', $email) }}"
                       class="form-control @error('email') is-invalid @enderror" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-muted">{{ __('New password') }}</label>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="form-label small fw-semibold text-muted">{{ __('Confirm password') }}</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('Reset password') }}</button>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100">{{ __('Back to login') }}</a>
        </form>
    </div>
</div>
@endsection
