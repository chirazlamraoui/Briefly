<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Member);
    }

    public function teamLead(): HasOne
    {
        return $this->hasOne(User::class)->where('role', UserRole::TeamLead);
    }

    public function briefs(): HasMany
    {
        return $this->hasMany(Brief::class);
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(Blocker::class)->orderBy('label');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}
