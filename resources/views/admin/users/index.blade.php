@extends('layouts.app')

@section('title', __('Users'))
@section('breadcrumb', __('Administration'))

@section('content')
@include('admin.partials.nav')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Manage users, their teams and roles.') }}</p>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">{{ __('New user') }}</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Teams') }}</th>
                        <th>{{ __('Projects') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td class="small">{{ $user->email }}</td>
                            <td class="small">{{ $adminUserService->teamSummary($user) }}</td>
                            <td class="small">{{ $adminUserService->projectSummary($user) }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ __('No users found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
