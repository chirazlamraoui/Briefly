<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'job_title', 'email', 'password', 'role', 'team_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function primaryTeam(): ?Team
    {
        if ($this->team) {
            return $this->team;
        }

        $team = $this->teams()->first();

        if ($team !== null && $this->team_id === null) {
            $this->update(['team_id' => $team->id]);
            $this->setRelation('team', $team);
        }

        return $team;
    }

    public function teamLabel(): string
    {
        if ($this->isAdmin()) {
            return __('All teams');
        }

        return $this->primaryTeam()?->name ?? __('No team');
    }

    public function teamsSummary(): string
    {
        $teams = $this->relationLoaded('teams')
            ? $this->teams->sortBy('name')->values()
            : $this->teams()->orderBy('name')->get();

        if ($teams->isEmpty() && $this->team) {
            return $this->team->name;
        }

        if ($teams->isEmpty()) {
            return '—';
        }

        if ($teams->count() === 1) {
            return $teams->first()->name;
        }

        return __(':count teams', ['count' => $teams->count()]);
    }

    public function primaryTeamId(): ?int
    {
        return $this->primaryTeam()?->id;
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(TeamUser::class)
            ->withPivot('is_team_lead')
            ->withTimestamps();
    }

    public function isTeamLeadOf(Team|int $team): bool
    {
        $teamId = $team instanceof Team ? $team->id : $team;

        return $this->teams()
            ->where('teams.id', $teamId)
            ->wherePivot('is_team_lead', true)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function managedTeamIds(): array
    {
        return $this->teams()
            ->wherePivot('is_team_lead', true)
            ->pluck('teams.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function managesTeam(?int $teamId): bool
    {
        if ($teamId === null || ! $this->isTeamLead()) {
            return false;
        }

        return $this->isTeamLeadOf($teamId);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function taskUpdates(): HasMany
    {
        return $this->hasMany(TaskUpdate::class);
    }

    public function isTeamLead(): bool
    {
        return $this->role === UserRole::TeamLead;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function belongsToTeam(?int $teamId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($teamId === null) {
            return false;
        }

        if ($this->team_id === $teamId) {
            return true;
        }

        return $this->teams()->where('teams.id', $teamId)->exists();
    }

    public function syncTeams(array $teamIds): void
    {
        $teamIds = collect($teamIds)->filter()->unique()->values()->all();
        $currentIds = $this->teams()->pluck('teams.id')->all();

        $toAttach = array_values(array_diff($teamIds, $currentIds));
        $toDetach = array_values(array_diff($currentIds, $teamIds));

        if ($toAttach !== []) {
            $attachData = collect($toAttach)
                ->mapWithKeys(fn (int $teamId) => [$teamId => ['is_team_lead' => false]])
                ->all();

            $this->teams()->attach($attachData);
        }

        if ($toDetach !== []) {
            $this->teams()->detach($toDetach);
        }

        if ($teamIds === []) {
            $this->update(['team_id' => null]);

            return;
        }

        if (! in_array((int) $this->team_id, array_map('intval', $teamIds), true)) {
            $this->update(['team_id' => $teamIds[0]]);
        }
    }
}
