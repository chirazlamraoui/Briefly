@extends('layouts.app')

@section('title', __('Task history'))
@section('breadcrumb', __('My Tasks'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('All progress updates you have saved on your tasks.') }}</p>
    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to tasks') }}</a>
</div>

<div class="card">
    @include('partials.task-update-list', ['updates' => $updates, 'showProject' => true])
</div>

<div class="mt-3">{{ $updates->links() }}</div>
@endsection
