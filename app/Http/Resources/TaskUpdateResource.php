<?php

namespace App\Http\Resources;

use App\Models\TaskUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskUpdate
 */
class TaskUpdateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'progress_done' => $this->progress_done,
            'progress_next' => $this->progress_next,
            'blocker_note' => $this->blocker_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'task' => new TaskResource($this->whenLoaded('task')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
