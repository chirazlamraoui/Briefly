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
}
