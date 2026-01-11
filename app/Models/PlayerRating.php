<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\PlayerRatingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[ObservedBy(PlayerRatingObserver::class)]
final class PlayerRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'game_id',
        'rating',
        'matches_played',
        'wins',
        'losses',
        'win_streak',
        'best_rating',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function getWinRateAttribute(): float
    {
        if ($this->matches_played === 0) {
            return 0.0;
        }

        return round(($this->wins / $this->matches_played) * 100, 1);
    }

    public function getKFactorAttribute(): int
    {
        if ($this->matches_played < 10) {
            return 40; // New players - faster calibration
        }

        if ($this->matches_played < 30) {
            return 32; // Established players
        }

        return 24; // Veterans - more stable
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (PlayerRating $rating): void {
            if (empty($rating->uuid)) {
                $rating->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'matches_played' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'win_streak' => 'integer',
            'best_rating' => 'integer',
        ];
    }
}
