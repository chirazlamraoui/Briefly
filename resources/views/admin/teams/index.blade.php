@extends('layouts.app')

@section('title', __('Team management'))
@section('breadcrumb', __('Administration'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Create teams and manage the organization structure.') }}</p>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">{{ __('Back to overview') }}</a>
        <a href="{{ route('admin.teams.create') }}" class="btn btn-primary">{{ __('New team') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Team') }}</th>
                        <th>{{ __('Team Lead') }}</th>
                        <th>{{ __('Members') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $team)
                        <tr>
                            <td class="fw-semibold">{{ $team->name }}</td>
                            <td class="small">{{ $team->teamLead?->name ?? '—' }}</td>
                            <td>{{ $team->member_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">{{ __('No teams found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
