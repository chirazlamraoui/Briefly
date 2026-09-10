@if($teamUpdates->isNotEmpty())
<div class="card mb-4">
    <div class="card-header">{{ __('Team updates reference') }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Member') }}</th>
                        <th>{{ __('Project') }}</th>
                        <th>{{ __('Task') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Done') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teamUpdates as $update)
                        <tr>
                            <td class="small">{{ $update->user->name }}</td>
                            <td class="small">{{ $update->task?->project?->name ?? '—' }}</td>
                            <td class="small">{{ $update->task?->title ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $update->status->badgeClass() }} status-badge">
                                    {{ $update->status->emoji() }}
                                </span>
                            </td>
                            <td class="small">{{ Str::limit($update->done(), 60) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
