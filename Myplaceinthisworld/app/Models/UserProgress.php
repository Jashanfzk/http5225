<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'activity_id',
        'completed_at',
        'is_favorite',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'is_favorite' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the activity for this progress.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
