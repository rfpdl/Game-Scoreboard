<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\GameObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(GameObserver::class)]
final class Game extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'rules',
        'icon',
        'min_players',
        'max_players',
        'is_active',
    ];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function playerRatings(): HasMany
    {
        return $this->hasMany(PlayerRating::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
