<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * class Playground
 * @package App\Models
 * @property int $id
 * @property int $area_id
 * @property string $name
 * @property float $price_per_30min
 * @property int $max_horizon_days
 * @property int $max_duration_minutes
 * @property bool $is_active
 */
class Playground extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'name',
        'price_per_30min',
        'max_horizon_days',
        'max_duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_30min' => 'decimal:2',
            'max_horizon_days' => 'integer',
            'max_duration_minutes' => 'integer',
            'is_active' => 'boolean',
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
}
