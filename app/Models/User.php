<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Website login uses a cookie. Phone login uses HasApiTokens (Sanctum).
 */
#[Fillable(['name', 'job_title', 'email', 'password', 'role', 'team_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** Main team, shown first on the profile. */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(TeamUser::class)
            ->withPivot('is_team_lead')
            ->withTimestamps();
    }

    /** Teams this user leads. */
    public function managedTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('is_team_lead', true);
    }

    /**
     * @return list<int>
     */
    public function managedTeamIds(): array
    {
        return $this->managedTeams()->pluck('teams.id')->map(fn ($id) => (int) $id)->all();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeamLead(): bool
    {
        return $this->managedTeams()->exists();
    }

    public function isTeamLeadOf(Team|int $team): bool
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        return $this->managedTeams()->where('teams.id', $teamId)->exists();
    }

    public function belongsToTeam(?int $teamId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($teamId === null) {
            return false;
        }

        return $this->team_id === $teamId || $this->teams()->where('teams.id', $teamId)->exists();
    }

    /** "Design", "3 teams" or "—", for headers and lists. */
    public function teamsSummary(): string
    {
        return match ($this->teams->count()) {
            0 => $this->team?->name ?? '—',
            1 => $this->teams->first()->name,
            default => __(':count teams', ['count' => $this->teams->count()]),
        };
    }
}
