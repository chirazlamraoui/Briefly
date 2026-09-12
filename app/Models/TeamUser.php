<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    protected $table = 'team_user';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'is_team_lead' => 'boolean',
        ];
    }
}
