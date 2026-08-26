<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_OWNER = 'owner';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'role',
        'status',
    ];

    protected $appends = [
        'profile_photo_url',
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_photo_url;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }

            $defaultDisk = config('filesystems.default', 'public');
            if ($defaultDisk === 'cloudinary') {
                return \Illuminate\Support\Facades\Storage::disk('cloudinary')->url($this->avatar);
            }

            $cleanPath = ltrim(str_replace('storage/', '', $this->avatar), '/');
            return asset('storage/' . $cleanPath);
        }
        return null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'deleted_at' => 'datetime',
        ];
    }

    public function boardingHouse(): HasOne
    {
        return $this->hasOne(BoardingHouse::class, 'owner_id');
    }

    public function verifiedBoardingHouses(): HasMany
    {
        return $this->hasMany(BoardingHouse::class, 'verified_by');
    }

    public function respondedReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'responded_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    protected static function booted(): void
    {
        static::saved(function ($user) {
            BoardingHouse::clearPublicCaches();
            if ($user->boardingHouse) {
                \Illuminate\Support\Facades\Cache::forget("boarding_house_public_details_{$user->boardingHouse->id}");
            }
        });

        static::deleted(function ($user) {
            BoardingHouse::clearPublicCaches();
            if ($user->boardingHouse) {
                \Illuminate\Support\Facades\Cache::forget("boarding_house_public_details_{$user->boardingHouse->id}");
            }
        });
    }
}