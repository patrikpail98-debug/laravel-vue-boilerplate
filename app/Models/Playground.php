<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * class Playground
 * @package App\Models
 * @property int $id
 * @property int $area_id
 * @property string $name
 * @property string|null $description
 * @property float $price_per_30min
 * @property int $max_horizon_days
 * @property int $max_duration_minutes
 * @property bool $is_active
 * @property string|null $image_path
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array|null $opening_hours
 * @property bool $allow_card_payment
 */
class Playground extends Model
{
    use HasFactory;

    /**
     * Ordered weekday keys used in the `opening_hours` JSON column, indexed
     * so that Carbon's ISO weekday (1 = Monday ... 7 = Sunday) maps directly
     * via DAY_KEYS[dayOfWeekIso - 1].
     */
    public const array DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    protected $fillable = [
        'area_id',
        'name',
        'description',
        'price_per_30min',
        'max_horizon_days',
        'max_duration_minutes',
        'is_active',
        'image_path',
        'latitude',
        'longitude',
        'opening_hours',
        'allow_card_payment',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'price_per_30min' => 'decimal:2',
            'max_horizon_days' => 'integer',
            'max_duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_hours' => 'array',
            'allow_card_payment' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * The opening-hours entry for the given date's weekday, e.g.
     * ['is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '20:00'].
     * Returns null if this playground has no opening hours configured at all
     * (treated as "no restriction" for backwards compatibility).
     */
    public function openingHoursFor(Carbon $date): ?array
    {
        if (!$this->opening_hours) {
            return null;
        }

        $key = self::DAY_KEYS[$date->dayOfWeekIso - 1];

        return $this->opening_hours[$key] ?? null;
    }
}
