<?php

namespace App\Enums;

enum UserRole: string
{
    case Member = 'MEMBER';
    case TeamLead = 'TEAM_LEAD';

    public function label(): string
    {
        return match ($this) {
            self::Member => __('Member'),
            self::TeamLead => __('Team Lead'),
        };
    }
}
