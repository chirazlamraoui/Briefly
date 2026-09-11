@extends('layouts.app')

@section('title', $task->title)
@section('breadcrumb', __('Task details'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h5 mb-2">{{ $task->title }}</h2>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @include('partials.status-pill', ['status' => $task->status])
            <span class="text-muted small">{{ $task->project->name }}</span>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($canEdit)
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit task') }}</a>
        @endif
        <a href="{{ auth()->user()->isTeamLead() ? route('projects.show', $task->project) : route('tasks.my') }}"
           class="btn btn-outline-secondary btn-sm">
            {{ auth()->user()->isTeamLead() ? __('Back to projects') : __('Back to tasks') }}
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">{{ __('Task details') }}</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">{{ __('Project') }}</dt>
                    <dd class="col-sm-8">{{ $task->project->name }}</dd>

                    <dt class="col-sm-4 text-muted">{{ __('Assignee') }}</dt>
                    <dd class="col-sm-8">{{ $task->assignee->name }}</dd>

                    <dt class="col-sm-4 text-muted">{{ __('Task status') }}</dt>
                    <dd class="col-sm-8">
                        @include('partials.status-pill', ['status' => $task->status])
                    </dd>

                    <dt class="col-sm-4 text-muted">{{ __('Description') }}</dt>
                    <dd class="col-sm-8">{{ $task->description ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if($canUpdateStatus)
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">{{ __('Update status') }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tasks.update-status', $task) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="status" class="form-label fw-semibold">{{ __('Task status') }}</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach(\App\Enums\TaskStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $task->status->value) === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Update status') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
