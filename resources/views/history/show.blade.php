@extends('layouts.app')

@section('title', __('Brief detail'))
@section('breadcrumb', __('Historique'))

@section('content')
<p class="text-muted mb-4">
    {{ $brief->team->name }}
    · {{ __('Published by :name on :date', [
        'name' => $brief->author->name,
        'date' => $brief->published_at?->translatedFormat('j F Y H:i'),
    ]) }}
</p>

<div class="card">
    <div class="card-body p-4">
        <h5 class="text-success">{{ __('Done') }}</h5>
        <p>{!! nl2br(e($brief->done() ?: '—')) !!}</p>

        <h5 class="text-muted">{{ __('In Progress') }}</h5>
        <p>{!! nl2br(e($brief->inProgress() ?: '—')) !!}</p>

        <h5 class="text-danger">{{ __('Blocker') }}</h5>
        <p class="mb-0">{!! nl2br(e($brief->blocker() ?: '—')) !!}</p>
    </div>
</div>

@include('partials.team-updates-reference', ['teamUpdates' => $teamUpdates ?? collect()])

<a href="{{ route('history.index') }}" class="btn btn-outline-secondary mt-3">{{ __('Back to history') }}</a>
@endsection
