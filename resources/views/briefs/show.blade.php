@extends('layouts.app')

@section('title', __('Brief du jour'))
@section('breadcrumb', __('Published brief'))

@section('content')
<p class="text-muted mb-4">
    {{ $brief->team->name }}
    @if($brief->isPublished())
        · {{ __('Published by :name on :date', [
            'name' => $brief->author->name,
            'date' => $brief->published_at?->translatedFormat('j F Y H:i'),
        ]) }}
    @endif
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

<a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mt-3">{{ __('Back to dashboard') }}</a>
@endsection
