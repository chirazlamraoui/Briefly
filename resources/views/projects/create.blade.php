@extends('layouts.app')

@section('title', __('New project'))
@section('breadcrumb', __('Projects'))

@section('content')
<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('projects.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">{{ __('Project name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                <textarea name="description" id="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Create project') }}</button>
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
