@extends('layouts.app')

@section('title', __('Historique'))
@section('breadcrumb', __('Brief archive'))

@section('content')
<div class="card mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date_from" class="form-label fw-semibold small">{{ __('Date from') }}</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label fw-semibold small">{{ __('Date to') }}</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="author_id" class="form-label fw-semibold small">{{ __('Author') }}</label>
                <select name="author_id" id="author_id" class="form-select">
                    <option value="">{{ __('All authors') }}</option>
                    @foreach($authors as $author)
                        <option value="{{ $author->id }}" @selected(request('author_id') == $author->id)>{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="search_in" class="form-label fw-semibold small">{{ __('Search in') }}</label>
                <select name="search_in" id="search_in" class="form-select">
                    <option value="all" @selected(request('search_in', 'all') === 'all')>{{ __('All sections') }}</option>
                    <option value="done" @selected(request('search_in') === 'done')>{{ __('Done') }}</option>
                    <option value="in_progress" @selected(request('search_in') === 'in_progress')>{{ __('In Progress') }}</option>
                    <option value="blocker" @selected(request('search_in') === 'blocker')>{{ __('Blocker') }}</option>
                </select>
            </div>
            <div class="col-md-8">
                <label for="q" class="form-label fw-semibold small">{{ __('Keyword') }}</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Search in brief content...') }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                <a href="{{ route('history.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($briefs as $brief)
            <a href="{{ route('history.show', $brief) }}" class="list-group-item list-group-item-action py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $brief->date->translatedFormat('j F Y') }}</strong>
                        <div class="small text-muted mt-1">{{ $brief->author->name }} · {{ Str::limit($brief->done(), 80) }}</div>
                    </div>
                    <span class="badge bg-success status-badge">{{ __('Published') }}</span>
                </div>
            </a>
        @empty
            <div class="list-group-item text-muted text-center py-5">{{ __('No published briefs found.') }}</div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $briefs->links() }}</div>
@endsection
