<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\PlayerRating;
use Spatie\LaravelData\Data;

final class LeaderboardEntryData extends Data
{
    public function __construct(
        public readonly int $rank,
        public readonly int $userId,
        public readonly string $userName,
        public readonly ?string $userAvatar,
        public readonly int $rating,
        public readonly int $matchesPlayed,
        public readonly int $wins,
        public readonly int $losses,
        public readonly float $winRate,
        public readonly int $winStreak,
    ) {}

    public static function fromModel(PlayerRating $rating, int $rank): self
    {
        $avatar = $rating->user->avatar;

        return new self(
            rank: $rank,
            userId: $rating->user_id,
            userName: $rating->user->name,
            userAvatar: $avatar ? '/storage/'.$avatar : null,
            rating: $rating->rating,
            matchesPlayed: $rating->matches_played,
            wins: $rating->wins,
            losses: $rating->losses,
            winRate: $rating->win_rate,
            winStreak: $rating->win_streak,
        );
    }
}
