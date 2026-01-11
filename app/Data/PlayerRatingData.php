<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\PlayerRating;
use Spatie\LaravelData\Data;

final class PlayerRatingData extends Data
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $userId,
        public readonly int $gameId,
        public readonly int $rating,
        public readonly int $matchesPlayed,
        public readonly int $wins,
        public readonly int $losses,
        public readonly int $winStreak,
        public readonly int $bestRating,
        public readonly float $winRate,
        public readonly ?string $userName = null,
        public readonly ?string $gameName = null,
        public readonly ?string $gameIcon = null,
    ) {}

    public static function fromModel(PlayerRating $rating): self
    {
        return new self(
            id: $rating->id,
            uuid: $rating->uuid,
            userId: $rating->user_id,
            gameId: $rating->game_id,
            rating: $rating->rating,
            matchesPlayed: $rating->matches_played,
            wins: $rating->wins,
            losses: $rating->losses,
            winStreak: $rating->win_streak,
            bestRating: $rating->best_rating,
            winRate: $rating->win_rate,
            userName: $rating->user?->name,
            gameName: $rating->game?->name,
            gameIcon: $rating->game?->icon,
        );
    }
}
