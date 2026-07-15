@extends('layouts.app')

@section('title', __('Team Updates'))
@section('breadcrumb', __('Team monitoring'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ $date->translatedFormat('l j F Y') }}</p>
    <form method="GET" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="form-control">
        <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
    </form>
</div>

@if($missing->isNotEmpty())
<div class="alert alert-warning">
    <strong>{{ __('Missing updates (:count):', ['count' => $missing->count()]) }}</strong>
    {{ $missing->pluck('name')->join(', ') }}
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Member') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Done') }}</th>
                        <th>{{ __('In Progress') }}</th>
                        <th>{{ __('Blocker') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        @php $update = $updates->get($member->id); @endphp
                        <tr class="{{ $update ? '' : 'table-warning' }}">
                            <td class="fw-semibold">
                                <a href="{{ route('team.members.show', $member) }}" class="text-decoration-none">{{ $member->name }}</a>
                            </td>
                            <td>
                                @if($update)
                                    <span class="badge bg-{{ $update->status->badgeClass() }} status-badge">
                                        {{ $update->status->emoji() }} {{ $update->status->label() }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary status-badge">{{ __('Not submitted') }}</span>
                                @endif
                            </td>
                            <td class="small">{{ $update?->done() ?: '—' }}</td>
                            <td class="small">{{ $update?->inProgress() ?: '—' }}</td>
                            <td class="small">{{ $update?->blockerLabel() ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ __('No members in this team.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
