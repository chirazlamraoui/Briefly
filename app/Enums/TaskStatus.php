<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'TODO';
    case InProgress = 'IN_PROGRESS';
    case Blocked = 'BLOCKED';
    case Done = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::Todo => __('To Do'),
            self::InProgress => __('In Progress'),
            self::Blocked => __('Blocked'),
            self::Done => __('Done'),
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Todo => 'status-pill status-pill--neutral',
            self::InProgress => 'status-pill status-pill--blue',
            self::Blocked => 'status-pill status-pill--red',
            self::Done => 'status-pill status-pill--green',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Todo => 'secondary',
            self::InProgress => 'primary',
            self::Blocked => 'danger',
            self::Done => 'success',
        };
    }

    public function progressPercent(): int
    {
        return match ($this) {
            self::Todo => 0,
            self::InProgress => 60,
            self::Blocked => 30,
            self::Done => 100,
        };
    }
}
