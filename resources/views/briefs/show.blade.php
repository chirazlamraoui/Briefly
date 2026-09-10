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

<div class="card" id="brief-content">
    <div class="card-body p-4">
        <h5 class="text-success">{{ __('Done') }}</h5>
        <p id="brief-done">{!! nl2br(e($brief->done() ?: '—')) !!}</p>

        <h5 class="text-muted">{{ __('In Progress') }}</h5>
        <p id="brief-in-progress">{!! nl2br(e($brief->inProgress() ?: '—')) !!}</p>

        <h5 class="text-danger">{{ __('Blocker') }}</h5>
        <p class="mb-0" id="brief-blocker">{!! nl2br(e($brief->blocker() ?: '—')) !!}</p>
    </div>
</div>

@include('partials.team-updates-reference', ['teamUpdates' => $teamUpdates ?? collect()])

<div class="d-flex flex-wrap gap-2 mt-3">
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">{{ __('Back to dashboard') }}</a>
    <button type="button" class="btn btn-outline-primary" id="copyBriefBtn">
        <i class="bi bi-clipboard me-1"></i>{{ __('Copy brief') }}
    </button>
    <a href="{{ route('briefs.export.pdf', $brief) }}" class="btn btn-outline-primary">
        <i class="bi bi-file-earmark-pdf me-1"></i>{{ __('Download PDF') }}
    </a>
</div>

<span class="visually-hidden" id="copyBriefText">@include('briefs.partials.plain-text', ['brief' => $brief])</span>

@push('scripts')
<script>
    const copyBtn = document.getElementById('copyBriefBtn');
    const copyText = document.getElementById('copyBriefText')?.textContent?.trim() ?? '';

    copyBtn?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(copyText);
            const original = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>{{ __('Copied!') }}';
            setTimeout(() => copyBtn.innerHTML = original, 2000);
        } catch (error) {
            window.prompt('{{ __('Copy brief') }}', copyText);
        }
    });
</script>
@endpush
@endsection
