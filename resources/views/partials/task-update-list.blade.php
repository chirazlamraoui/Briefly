<div class="list-group list-group-flush">
    @forelse($updates as $update)
        <div class="list-group-item py-3">
            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                <div>
                    <strong>{{ $update->created_at->translatedFormat('l j F Y H:i') }}</strong>
                    <div class="small mt-1">
                        <a href="{{ route('tasks.show', $update->task) }}" class="text-decoration-none">{{ $update->task->title }}</a>
                        @if(! empty($showProject))
                            <span class="text-muted">· {{ $update->task->project->name }}</span>
                        @endif
                    </div>
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
        <div class="list-group-item text-muted text-center py-4">{{ __('No progress history yet.') }}</div>
    @endforelse
</div>
