<?php

namespace App\Models;

use App\Enums\UpdateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyUpdate extends Model
{
    use HasFactory;

    protected $table = 'updates';

    protected $fillable = [
        'user_id',
        'task_id',
        'date',
        'content',
        'status',
        'blocker_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'content' => 'array',
            'status' => UpdateStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(Blocker::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function done(): string
    {
        return $this->content['done'] ?? '';
    }

    public function inProgress(): string
    {
        return $this->content['in_progress'] ?? '';
    }

    public function blockerLabel(): string
    {
        if ($this->blocker) {
            return $this->blocker->label;
        }

        return trim($this->content['blocker'] ?? '');
    }

    public function blockerTypeForForm(): string
    {
        if ($this->blocker_id) {
            return 'existing';
        }

        if ($this->blockerLabel() !== '') {
            return 'existing';
        }

        return 'none';
    }
}
