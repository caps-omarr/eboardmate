<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public const ACTION_RESERVATION_APPROVED = 'reservation_approved';
    public const ACTION_RESERVATION_REJECTED = 'reservation_rejected';

    public const ACTION_LISTING_VERIFIED = 'listing_verified';
    public const ACTION_LISTING_REJECTED = 'listing_rejected';
    public const ACTION_LISTING_DEACTIVATED = 'listing_deactivated';
    public const ACTION_LISTING_REACTIVATED = 'listing_reactivated';
    public const ACTION_COORDINATES_UPDATED = 'coordinates_updated';

    public const ACTION_PHOTO_UPLOADED = 'photo_uploaded';
    public const ACTION_PHOTO_SET_PRIMARY = 'photo_set_primary';
    public const ACTION_PHOTO_DELETED = 'photo_deleted';

    public const ACTION_OWNER_CREATED = 'owner_created';
    public const ACTION_BOARDING_HOUSE_CREATED = 'boarding_house_created';

    protected $fillable = [
        'user_id',
        'boarding_house_id',
        'reservation_id',
        'action',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boardingHouse(): BelongsTo
    {
        return $this->belongsTo(BoardingHouse::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}