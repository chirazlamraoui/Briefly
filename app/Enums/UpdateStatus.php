<?php

namespace App\Enums;

enum UpdateStatus: string
{
    case Green = 'GREEN';
    case Orange = 'ORANGE';
    case Red = 'RED';

    public function label(): string
    {
        return match ($this) {
            self::Green => __('On Track'),
            self::Orange => __('Attention'),
            self::Red => __('Blocked'),
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Green => '🟢',
            self::Orange => '🟠',
            self::Red => '🔴',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Green => 'status-dot status-dot--green',
            self::Orange => 'status-dot status-dot--orange',
            self::Red => 'status-dot status-dot--red',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Green => 'status-pill status-pill--green',
            self::Orange => 'status-pill status-pill--orange',
            self::Red => 'status-pill status-pill--red',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Orange => 'warning',
            self::Red => 'danger',
        };
    }
}
