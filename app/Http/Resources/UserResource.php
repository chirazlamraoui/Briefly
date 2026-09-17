<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape of a user for the phone (and any JSON client).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'job_title' => $this->job_title,
            'email' => $this->email,
            'role' => $this->role->value,
            'team_id' => $this->team_id,
            'is_team_lead' => $this->relationLoaded('teams')
                ? $this->teams->contains(fn ($team) => (bool) $team->pivot?->is_team_lead)
                : $this->isTeamLead(),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
        ];
    }
}
