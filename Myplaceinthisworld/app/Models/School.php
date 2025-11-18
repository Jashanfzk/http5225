<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'school_name',
        'school_board',
        'address',
    ];

    /**
     * Get the users for the school.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the memberships for the school.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get the gallery posts for the school.
     */
    public function galleryPosts(): HasMany
    {
        return $this->hasMany(GalleryPost::class);
    }

    /**
     * Get the owner of the school.
     */
    public function owner()
    {
        return $this->users()->where('is_owner', true)->first();
    }
}
