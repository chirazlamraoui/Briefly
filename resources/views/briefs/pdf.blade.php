<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Brief du jour') }} — {{ $brief->date->format('Y-m-d') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18181b; line-height: 1.5; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #71717a; font-size: 11px; margin-bottom: 20px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .done { color: #047857; }
        .progress { color: #52525b; }
        .blocker { color: #be123c; }
        p { margin: 0 0 8px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>{{ __('Brief du jour') }} — {{ $brief->team->name }}</h1>
    <div class="meta">
        {{ $brief->date->translatedFormat('l j F Y') }}
        @if($brief->isPublished())
            · {{ __('Published by :name on :date', [
                'name' => $brief->author->name,
                'date' => $brief->published_at?->translatedFormat('j F Y H:i'),
            ]) }}
        @endif
    </div>

    <h2 class="done">{{ __('Done') }}</h2>
    <p>{{ $brief->done() ?: '—' }}</p>

    <h2 class="progress">{{ __('In Progress') }}</h2>
    <p>{{ $brief->inProgress() ?: '—' }}</p>

    <h2 class="blocker">{{ __('Blocker') }}</h2>
    <p>{{ $brief->blocker() ?: '—' }}</p>

    @if(($teamUpdates ?? collect())->isNotEmpty())
        <h2>{{ __('Team updates reference') }}</h2>
        <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 11px;">
            <tr style="background: #f4f4f5;">
                <th align="left">{{ __('Member') }}</th>
                <th align="left">{{ __('Project') }}</th>
                <th align="left">{{ __('Task') }}</th>
                <th align="left">{{ __('Status') }}</th>
            </tr>
            @foreach($teamUpdates as $update)
                <tr>
                    <td>{{ $update->user->name }}</td>
                    <td>{{ $update->task?->project?->name ?? '—' }}</td>
                    <td>{{ $update->task?->title ?? '—' }}</td>
                    <td>{{ $update->status->emoji() }} {{ $update->status->label() }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
