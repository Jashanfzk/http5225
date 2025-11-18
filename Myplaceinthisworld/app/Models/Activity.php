<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $fillable = [
        'division_id',
        'title',
        'slug',
        'description',
        'google_doc_id',
        'content',
    ];

    /**
     * Get the division that owns the activity.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the user progress for this activity.
     */
    public function userProgress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }
}
