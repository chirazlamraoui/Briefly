@extends('layouts.app')

@section('title', __('Brief du jour'))
@section('breadcrumb', __('Daily brief editor'))

@section('content')
<p class="text-muted mb-4">
    {{ $brief->date->translatedFormat('l j F Y') }}
    · <span class="status-pill status-pill--neutral">{{ $brief->status->label() }}</span>
</p>

@include('partials.team-updates-reference', ['teamUpdates' => $teamUpdates])

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('briefs.update', $brief) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="done" class="form-label fw-semibold text-success">{{ __('Done') }}</label>
                <textarea name="done" id="done" rows="5" class="form-control @error('done') is-invalid @enderror" required>{{ old('done', $brief->done()) }}</textarea>
                @error('done')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="in_progress" class="form-label fw-semibold text-muted">{{ __('In Progress') }}</label>
                <textarea name="in_progress" id="in_progress" rows="5" class="form-control @error('in_progress') is-invalid @enderror" required>{{ old('in_progress', $brief->inProgress()) }}</textarea>
                @error('in_progress')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="blocker" class="form-label fw-semibold text-danger">{{ __('Blocker') }}</label>
                <textarea name="blocker" id="blocker" rows="4" class="form-control @error('blocker') is-invalid @enderror">{{ old('blocker', $brief->blocker()) }}</textarea>
                @error('blocker')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">{{ __('Save draft') }}</button>
                <a href="{{ route('briefs.preview', $brief) }}" class="btn btn-outline-primary">{{ __('Preview before publish') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
