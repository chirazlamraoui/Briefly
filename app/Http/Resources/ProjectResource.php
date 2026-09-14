<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'tasks_count' => $this->whenCounted('tasks'),
            'teams_summary' => $this->when($this->relationLoaded('teams'), fn () => $this->teamsSummary()),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
        ];
    }
}
