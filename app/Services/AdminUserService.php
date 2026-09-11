<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    /**
     * @return Collection<int, User>
     */
    public function assignableUsers(): Collection
    {
        return User::query()
            ->with('team')
            ->where('role', '!=', UserRole::Admin)
            ->orderBy('name')
            ->get();
    }

    public function assignTeamAndRole(User $user, int $teamId, UserRole $role): void
    {
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => __('Administrators cannot be reassigned through this form.'),
            ]);
        }

        if (! in_array($role, [UserRole::Member, UserRole::TeamLead], true)) {
            throw ValidationException::withMessages([
                'role' => __('The selected role is invalid.'),
            ]);
        }

        if ($role === UserRole::TeamLead) {
            User::query()
                ->where('team_id', $teamId)
                ->where('role', UserRole::TeamLead)
                ->where('id', '!=', $user->id)
                ->update(['role' => UserRole::Member]);
        }

        $user->update([
            'team_id' => $teamId,
            'role' => $role,
        ]);
    }

    /**
     * @param  array{name: string, email: string, password: string, team_id: int, role: string}  $data
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Member,
            'team_id' => $data['team_id'],
        ]);

        $this->assignTeamAndRole($user, $data['team_id'], UserRole::from($data['role']));

        return $user->fresh(['team']);
    }
}
