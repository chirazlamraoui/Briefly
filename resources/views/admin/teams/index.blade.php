@extends('layouts.app')

@section('title', __('Teams'))
@section('breadcrumb', __('Administration'))

@section('content')
@include('admin.partials.nav')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Create teams and manage their members.') }}</p>
    <a href="{{ route('admin.teams.create') }}" class="btn btn-primary">{{ __('New team') }}</a>
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $team)
                        <tr>
                            <td class="fw-semibold">{{ $team->name }}</td>
                            <td class="small">{{ $team->teamLead?->name ?? '—' }}</td>
                            <td>{{ $team->member_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.teams.show', $team) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No teams found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
