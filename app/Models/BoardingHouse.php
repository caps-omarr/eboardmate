<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardingHouse extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DEACTIVATED = 'deactivated';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'location_description',
        'address',
        'latitude',
        'longitude',
        'rent_price',
        'total_rooms',
        'available_rooms',
        'total_bedspaces',
        'available_bedspaces',
        'amenities',
        'rules',
        'status',
        'is_verified',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'deactivated_reason',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'rent_price' => 'decimal:2',
            'total_rooms' => 'integer',
            'available_rooms' => 'integer',
            'total_bedspaces' => 'integer',
            'available_bedspaces' => 'integer',
            'amenities' => 'array',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(BoardingHousePhoto::class);
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(BoardingHousePhoto::class)->where('is_primary', true);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->is_verified === true;
    }

    public function isFull(): bool
    {
        return $this->available_rooms <= 0 && $this->available_bedspaces <= 0;
    }

    public function hasAvailableSlot(): bool
    {
        return $this->available_rooms > 0 || $this->available_bedspaces > 0;
    }
}