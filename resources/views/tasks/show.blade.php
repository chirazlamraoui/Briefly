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
            @if($task->completed_at)
                <span class="text-muted small">· {{ __('Completed on :date', ['date' => $task->completed_at->translatedFormat('j F Y H:i')]) }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($canEdit)
            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit task') }}</a>
        @endif
        <a href="{{ auth()->user()->isTeamLead() ? route('team.tasks') : route('tasks.my') }}"
           class="btn btn-outline-secondary btn-sm">
            {{ auth()->user()->isTeamLead() ? __('Back to team tasks') : __('Back to tasks') }}
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-{{ $canUpdateProgress ? '7' : '12' }}">
        <div class="card h-100">
            <div class="card-header">{{ __('Task details') }}</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">{{ __('Project') }}</dt>
                    <dd class="col-sm-8">{{ $task->project->name }}</dd>

                    <dt class="col-sm-4 text-muted">{{ __('Assignee') }}</dt>
                    <dd class="col-sm-8">{{ $task->assignee->name }}</dd>

                    <dt class="col-sm-4 text-muted">{{ __('What to do') }}</dt>
                    <dd class="col-sm-8">{{ $task->description ?: '—' }}</dd>

                    @if($task->progress_done)
                        <dt class="col-sm-4 text-muted">{{ __('Done so far') }}</dt>
                        <dd class="col-sm-8">{{ $task->progress_done }}</dd>
                    @endif

                    @if($task->progress_next)
                        <dt class="col-sm-4 text-muted">{{ __('In progress') }}</dt>
                        <dd class="col-sm-8">{{ $task->progress_next }}</dd>
                    @endif

                    @if($task->blocker_note)
                        <dt class="col-sm-4 text-muted">{{ __('Blocker') }}</dt>
                        <dd class="col-sm-8 text-danger">{{ $task->blocker_note }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    @if($canUpdateProgress)
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">{{ __('Update progress') }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tasks.update-progress', $task) }}">
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

                        <div class="mb-3">
                            <label for="progress_done" class="form-label fw-semibold">{{ __('Done so far') }}</label>
                            <textarea name="progress_done" id="progress_done" rows="3"
                                      class="form-control @error('progress_done') is-invalid @enderror"
                                      placeholder="{{ __('What have you completed?') }}">{{ old('progress_done', $task->progress_done) }}</textarea>
                            @error('progress_done')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="progress_next" class="form-label fw-semibold">{{ __('Still working on') }}</label>
                            <textarea name="progress_next" id="progress_next" rows="3"
                                      class="form-control @error('progress_next') is-invalid @enderror"
                                      placeholder="{{ __('What are you doing next?') }}">{{ old('progress_next', $task->progress_next) }}</textarea>
                            @error('progress_next')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4" id="blockerField">
                            <label for="blocker_note" class="form-label fw-semibold text-danger">{{ __('Blocker') }}</label>
                            <textarea name="blocker_note" id="blocker_note" rows="2"
                                      class="form-control @error('blocker_note') is-invalid @enderror"
                                      placeholder="{{ __('What is blocking you?') }}">{{ old('blocker_note', $task->blocker_note) }}</textarea>
                            @error('blocker_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Save progress') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="card mt-4">
    <div class="card-header">{{ __('Task history') }}</div>
    <div class="list-group list-group-flush">
        @forelse($task->updates as $update)
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                    <div>
                        <strong>{{ $update->created_at->translatedFormat('l j F Y H:i') }}</strong>
                        <span class="text-muted small ms-2">{{ $update->user->name }}</span>
                    </div>
                    @include('partials.status-pill', ['status' => $update->status])
                </div>
                <div class="small">
                    @if($update->progress_done)
                        <div class="mb-1"><span class="text-success fw-semibold">{{ __('Done so far') }}:</span> {{ $update->progress_done }}</div>
                    @endif
                    @if($update->progress_next)
                        <div class="mb-1"><span class="text-muted fw-semibold">{{ __('Still working on') }}:</span> {{ $update->progress_next }}</div>
                    @endif
                    @if($update->blocker_note)
                        <div><span class="text-danger fw-semibold">{{ __('Blocker') }}:</span> {{ $update->blocker_note }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted text-center py-4">{{ __('No progress logged yet.') }}</div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const statusSelect = document.getElementById('status');
        const blockerField = document.getElementById('blockerField');
        if (!statusSelect || !blockerField) return;

        function syncBlockerField() {
            blockerField.classList.toggle('d-none', statusSelect.value !== '{{ \App\Enums\TaskStatus::Blocked->value }}');
        }

        statusSelect.addEventListener('change', syncBlockerField);
        syncBlockerField();
    })();
</script>
@endpush
@endsection
