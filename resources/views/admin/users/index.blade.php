@extends('layouts.app')

@section('title', __('User assignments'))
@section('breadcrumb', __('Administration'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Assign users to teams and set their role.') }}</p>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">{{ __('New user') }}</a>
        <a href="{{ route('admin.teams.create') }}" class="btn btn-outline-primary">{{ __('New team') }}</a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">{{ __('Back to overview') }}</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Team') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td class="small">{{ $user->email }}</td>
                            <td>{{ $user->team?->name ?? '—' }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('Edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ __('No users found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
