<?php

namespace App\Enums;

enum BriefStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
        };
    }
}
