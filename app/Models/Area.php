<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * class Area
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $address
 */
class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
    ];

    public function playgrounds(): HasMany
    {
        return $this->hasMany(Playground::class);
    }
}
