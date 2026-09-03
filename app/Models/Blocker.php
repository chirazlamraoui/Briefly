<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blocker extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'label',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(DailyUpdate::class);
    }
}
