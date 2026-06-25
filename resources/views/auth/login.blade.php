@extends('layouts.app')

@section('title', __('Sign in'))

@section('content')
<div class="card login-card">
    <div class="login-accent"></div>
    <div class="login-header">
        <div class="login-logo"><i class="bi bi-lightning-charge"></i></div>
        <h1 class="h4 mb-1 fw-semibold">{{ __('Briefly') }}</h1>
        <p class="text-muted small mb-0">{{ __('Daily team progress tracking') }}</p>
    </div>
    <div class="card-body p-4 pt-3">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-muted">{{ __('Email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-muted">{{ __('Password') }}</label>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check mb-0">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label small">{{ __('Remember me') }}</label>
                </div>
                <a href="{{ route('password.request') }}" class="small">{{ __('Forgot password?') }}</a>
            </div>
            <button type="submit" class="btn btn-primary w-100">{{ __('Sign in') }}</button>
        </form>
    </div>
</div>
@endsection
