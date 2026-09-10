<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'TODO';
    case InProgress = 'IN_PROGRESS';
    case Done = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::Todo => __('To Do'),
            self::InProgress => __('In Progress'),
            self::Done => __('Done'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Todo => 'secondary',
            self::InProgress => 'primary',
            self::Done => 'success',
        };
    }
}
