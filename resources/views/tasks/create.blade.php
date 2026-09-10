@extends('layouts.app')

@section('title', __('New task'))
@section('breadcrumb', $project->name)

@section('content')
<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('tasks.store', $project) }}">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">{{ __('Task title') }}</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                       class="form-control @error('title') is-invalid @enderror" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                <textarea name="description" id="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="assigned_to" class="form-label fw-semibold">{{ __('Assign to') }}</label>
                <select name="assigned_to" id="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" required>
                    <option value="">{{ __('Choose a member') }}</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected((string) old('assigned_to') === (string) $member->id)>
                            {{ $member->name }}
                        </option>
                    @endforeach
                </select>
                @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="status" class="form-label fw-semibold">{{ __('Task status') }}</label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach(\App\Enums\TaskStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', \App\Enums\TaskStatus::Todo->value) === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Create task') }}</button>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
