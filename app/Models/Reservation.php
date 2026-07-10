<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * class Reservation
 * @package App\Models
 * @property int $id
 * @property int $playground_id
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_email
 * @property string $customer_phone
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property string $variable_symbol
 * @property float $total_price
 * @property string $status
 * @property string|null $verification_token
 * @property Carbon|null $verified_at
 * @property string|null $admin_note
 */
class Reservation extends Model
{
    use HasFactory;

    public const STATUS_UNVERIFIED = 'unverified';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses that still hold a slot in the calendar (block availability).
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_UNVERIFIED,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
    ];

    /**
     * Minutes an unverified reservation holds its slot before it can be expired.
     */
    public const HOLD_MINUTES = 15;

    protected $fillable = [
        'playground_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'start_time',
        'end_time',
        'variable_symbol',
        'total_price',
        'status',
        'verification_token',
        'verified_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'total_price' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function playground(): BelongsTo
    {
        return $this->belongsTo(Playground::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpiredHold(): bool
    {
        return $this->status === self::STATUS_UNVERIFIED
            && $this->created_at->lt(Carbon::now()->subMinutes(self::HOLD_MINUTES));
    }

    /**
     * start_time/end_time are stored as true UTC instants; emails, PDFs and
     * anywhere else showing a wall-clock time to a human must display it in
     * the facility's own timezone, not the server's (UTC).
     */
    public function startTimeLocal(): Carbon
    {
        return $this->start_time->clone()->setTimezone(config('app.facility_timezone'));
    }

    public function endTimeLocal(): Carbon
    {
        return $this->end_time->clone()->setTimezone(config('app.facility_timezone'));
    }
}
