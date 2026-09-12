<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function teamsSummary(): string
    {
        $teamsCount = (int) ($this->teams_count ?? $this->teams()->count());

        if ($teamsCount === 0) {
            return '—';
        }

        if ($teamsCount === 1) {
            return $this->teams->first()?->name ?? '—';
        }

        return __(':count teams', ['count' => $teamsCount]);
    }
}
