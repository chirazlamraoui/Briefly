@extends('layouts.app')

@section('title', __('Brief du jour'))
@section('breadcrumb', __('Preview mode'))

@section('content')
<div class="alert alert-light border mb-4">
    <strong>{{ __('Preview mode') }}</strong> — {{ __('This is how members will see the brief after publication.') }}
</div>

<p class="text-muted mb-4">
    {{ $brief->date->translatedFormat('l j F Y') }}
    · <span class="status-pill status-pill--neutral">{{ $brief->status->label() }}</span>
</p>

<div class="card mb-4">
    <div class="card-body p-4">
        <h5 class="text-success">{{ __('Done') }}</h5>
        <p>{!! nl2br(e($brief->done() ?: '—')) !!}</p>

        <h5 class="text-muted">{{ __('In Progress') }}</h5>
        <p>{!! nl2br(e($brief->inProgress() ?: '—')) !!}</p>

        <h5 class="text-danger">{{ __('Blocker') }}</h5>
        <p class="mb-0">{!! nl2br(e($brief->blocker() ?: '—')) !!}</p>
    </div>
</div>

@include('partials.team-updates-reference', ['teamUpdates' => $teamUpdates])

<div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('briefs.today') }}" class="btn btn-outline-secondary">{{ __('Back to edit') }}</a>
    <form method="POST" action="{{ route('briefs.publish', $brief) }}" onsubmit="return confirm(@json(__('Publish this brief? Members will be able to read it.')));">
        @csrf
        <button type="submit" class="btn btn-success">{{ __('Confirm publish') }}</button>
    </form>
</div>
@endsection
