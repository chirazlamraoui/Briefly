<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'progress_done' => $this->progress_done,
            'progress_next' => $this->progress_next,
            'blocker_note' => $this->blocker_note,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'updates' => TaskUpdateResource::collection($this->whenLoaded('updates')),
            'team_label' => $this->when(array_key_exists('team_label', $this->resource->getAttributes()), $this->team_label),
            'team' => new TeamResource($this->when(isset($this->team_context), $this->team_context)),
        ];
    }
}
