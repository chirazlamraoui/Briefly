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

    public function badgeClass(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Orange => 'warning',
            self::Red => 'danger',
        };
    }
}
