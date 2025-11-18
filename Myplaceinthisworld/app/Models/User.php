<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'is_owner',
        'current_sub_account_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
        ];
    }

    /**
     * Get the school that owns the user.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the current sub-account.
     */
    public function currentSubAccount()
    {
        return $this->belongsTo(User::class, 'current_sub_account_id');
    }

    /**
     * Get all sub-accounts for this user (if owner).
     */
    public function subAccounts()
    {
        return $this->hasMany(User::class, 'current_sub_account_id');
    }

    /**
     * Get the user's progress.
     */
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Get the user's gallery posts.
     */
    public function galleryPosts()
    {
        return $this->hasMany(GalleryPost::class);
    }

    /**
     * Get the effective user ID for progress tracking.
     * Returns the current sub-account ID if set, otherwise returns the owner's ID.
     */
    public function getEffectiveUserId(): int
    {
        if ($this->current_sub_account_id) {
            return $this->current_sub_account_id;
        }
        return $this->id;
    }

    /**
     * Get the effective user for progress tracking.
     */
    public function getEffectiveUser(): self
    {
        if ($this->current_sub_account_id) {
            return User::findOrFail($this->current_sub_account_id);
        }
        return $this;
    }

    /**
     * Check if user has access to a division.
     */
    public function hasAccessToDivision($divisionSlug)
    {
        if (!$this->school) {
            return false;
        }

        // High School is always accessible
        if ($divisionSlug === 'high-school') {
            return true;
        }

        // Check if school has active membership for this division
        $membershipType = match($divisionSlug) {
            'primary' => 'primary',
            'junior-intermediate' => 'junior_intermediate',
            default => null,
        };

        if (!$membershipType) {
            return false;
        }

        return $this->school->memberships()
            ->where('membership_type', $membershipType)
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
