<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const EMAIL_STATUS_SENT = 'sent';
    public const EMAIL_STATUS_FAILED = 'failed';
    public const EMAIL_STATUS_NOT_CONFIGURED = 'not_configured';

    protected $fillable = [
        'boarding_house_id',
        'reference_code',
        'guest_name',
        'guest_email',
        'guest_phone',
        'preferred_move_in_date',
        'message',
        'status',
        'owner_response',
        'responded_at',
        'responded_by',
        'expires_at',
        'expired_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'email_notification_sent_at',
        'email_notification_status',
        'email_notification_error',
        'submission_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'preferred_move_in_date' => 'date',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'email_notification_sent_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function boardingHouse(): BelongsTo
    {
        return $this->belongsTo(BoardingHouse::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
        ], true);
    }

    public function hasExpiredByTime(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at !== null
            && now()->greaterThanOrEqualTo($this->expires_at);
    }
}