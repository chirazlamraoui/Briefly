<?php

namespace App\Enums;

enum UserRole: string
{
    case Member = 'MEMBER';
    case TeamLead = 'TEAM_LEAD';
    case Admin = 'ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::Member => __('Member'),
            self::TeamLead => __('Team Lead'),
            self::Admin => __('Administrator'),
        };
    }
}
