<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TeamUser::class)
            ->withPivot('is_team_lead')
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->assignedUsers()->wherePivot('is_team_lead', false);
    }

    public function teamLead(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            TeamUser::class,
            'team_id',
            'id',
            'id',
            'user_id',
        )->where('team_user.is_team_lead', true);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}
