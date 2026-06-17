<?php

namespace App\Models;

use App\Enums\BriefStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brief extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'date',
        'content',
        'status',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'content' => 'array',
            'status' => BriefStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === BriefStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === BriefStatus::Draft;
    }

    public function publish(): void
    {
        $this->update([
            'status' => BriefStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function done(): string
    {
        return $this->content['done'] ?? '';
    }

    public function inProgress(): string
    {
        return $this->content['in_progress'] ?? '';
    }

    public function blocker(): string
    {
        return $this->content['blocker'] ?? '';
    }
}
