<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryPost extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'title',
        'content',
        'image',
    ];

    /**
     * Get the user that created the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the school that owns the post.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
