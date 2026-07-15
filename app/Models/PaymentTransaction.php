<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log of every Nexi XPay payment attempt for a reservation - one row
 * per attempt, kept (not overwritten) so disputes/support can trace exactly
 * what the gateway reported at each step.
 *
 * @property int $id
 * @property string $reservation_id
 * @property string $provider
 * @property string $order_id
 * @property string $status
 * @property int $amount_cents
 * @property string $currency
 * @property string|null $hosted_page_url
 * @property string|null $security_token
 * @property string|null $last_operation_type
 * @property array|null $raw_response
 * @property \Illuminate\Support\Carbon|null $verified_at
 */
class PaymentTransaction extends Model
{
    use HasFactory;

    public const PROVIDER_NEXI_XPAY = 'nexi_xpay';

    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';

    /**
     * Transaction statuses that are still awaiting a definitive outcome from
     * the gateway and are safe to reuse instead of creating a new order.
     */
    public const REUSABLE_STATUSES = [
        self::STATUS_PENDING,
    ];

    protected $fillable = [
        'reservation_id',
        'provider',
        'order_id',
        'status',
        'amount_cents',
        'currency',
        'hosted_page_url',
        'security_token',
        'last_operation_type',
        'raw_response',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'raw_response' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
