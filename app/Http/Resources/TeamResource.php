<?php

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_team_lead' => $this->when(
                $this->pivot !== null && isset($this->pivot->is_team_lead),
                fn () => (bool) $this->pivot->is_team_lead,
            ),
            'member_count' => $this->when(isset($this->resource->member_count), $this->member_count),
            'team_lead' => new UserResource($this->whenLoaded('teamLead')),
            'assigned_users' => UserResource::collection($this->whenLoaded('assignedUsers')),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
        ];
    }
}
