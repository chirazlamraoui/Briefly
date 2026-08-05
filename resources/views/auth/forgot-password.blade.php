@extends('layouts.app')

@section('title', __('Reset password'))

@section('content')
<div class="card login-card">
    <div class="login-accent"></div>
    <div class="login-header">
        <h1 class="h5 mb-1 fw-semibold">{{ __('Reset password') }}</h1>
        <p class="text-muted small mb-0">{{ __('Email') }}</p>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-muted">{{ __('Email') }}</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('Send reset link') }}</button>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100">{{ __('Back to login') }}</a>
        </form>
    </div>
</div>
@endsection
