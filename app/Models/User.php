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
use Illuminate\Support\Collection;

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

    public function primaryTeamId(): ?int
    {
        return $this->primaryTeam()?->id;
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function accessibleProjects(): Collection
    {
        $teamIds = $this->teams()->pluck('teams.id');

        if ($this->team_id && ! $teamIds->contains($this->team_id)) {
            $teamIds->push($this->team_id);
        }

        if ($teamIds->isEmpty()) {
            return collect();
        }

        return Project::query()
            ->whereHas('teams', fn ($query) => $query->whereIn('teams.id', $teamIds))
            ->orderBy('name')
            ->get();
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
            $this->teams()->attach($toAttach);
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
