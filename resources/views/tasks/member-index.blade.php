@extends('layouts.app')

@section('title', __('My Tasks'))
@section('breadcrumb', __('Assigned tasks'))

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <p class="text-muted mb-0">{{ __('Track your assigned work and update progress on each task.') }}</p>
    <a href="{{ route('tasks.history') }}" class="btn btn-outline-secondary btn-sm">{{ __('Task history') }}</a>
</div>

@php
    $sections = [
        \App\Enums\TaskStatus::Blocked->value => __('Blocked'),
        \App\Enums\TaskStatus::InProgress->value => __('In Progress'),
        \App\Enums\TaskStatus::Todo->value => __('To Do'),
        \App\Enums\TaskStatus::Done->value => __('Done'),
    ];
@endphp

@foreach($sections as $statusValue => $label)
    @php $sectionTasks = $grouped->get($statusValue, collect()); @endphp
    @if($sectionTasks->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header">{{ $label }} ({{ $sectionTasks->count() }})</div>
            <div class="list-group list-group-flush">
                @foreach($sectionTasks as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="list-group-item list-group-item-action py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold">{{ $task->title }}</div>
                                <div class="small text-muted">{{ $task->project->name }}</div>
                                @if($task->blocker_note)
                                    <div class="small text-danger mt-1">{{ Str::limit($task->blocker_note, 80) }}</div>
                                @endif
                            </div>
                            @include('partials.status-pill', ['status' => $task->status])
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endforeach

@if($tasks->isEmpty())
    <div class="card">
        <div class="card-body text-center text-muted py-5">{{ __('No tasks assigned to you yet.') }}</div>
    </div>
@endif
@endsection
