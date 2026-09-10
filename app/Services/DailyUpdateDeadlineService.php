<?php

namespace App\Services;

use App\Models\DailyUpdate;
use Carbon\Carbon;

class DailyUpdateDeadlineService
{
    public function deadlineToday(?Carbon $date = null): Carbon
    {
        $date ??= today();
        $time = config('briefly.daily_update_deadline', '17:00');

        return Carbon::parse($date->format('Y-m-d').' '.$time, config('app.timezone'));
    }

    public function isPastDeadline(?Carbon $now = null): bool
    {
        $now ??= now();

        return $now->greaterThan($this->deadlineToday($now->copy()->startOfDay()));
    }

    /**
     * @return array{past: bool, deadline: Carbon, deadline_label: string, timezone: string}|null
     */
    public function reminderFor(?DailyUpdate $todayUpdate): ?array
    {
        if ($todayUpdate !== null) {
            return null;
        }

        $deadline = $this->deadlineToday();

        return [
            'past' => $this->isPastDeadline(),
            'deadline' => $deadline,
            'deadline_label' => $deadline->translatedFormat('H:i'),
            'timezone' => config('app.timezone'),
        ];
    }
}
