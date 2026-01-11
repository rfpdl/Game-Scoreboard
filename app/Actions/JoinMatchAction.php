<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Domain\Rating\Services\EloCalculator;
use App\Models\GameMatch;
use App\Models\PlayerRating;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

final class JoinMatchAction
{
    public function execute(User $user, GameMatch $match): GameMatch
    {
        if (! $match->isPending()) {
            throw new DomainException('Cannot join a match that is not pending');
        }

        if ($match->players()->where('user_id', $user->id)->exists()) {
            throw new DomainException('You are already in this match');
        }

        // Check if user has another pending/confirmed match for this game
        $existingMatch = GameMatch::where('game_id', $match->game_id)
            ->where('id', '!=', $match->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('players', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        if ($existingMatch) {
            throw new DomainException('You already have an active match for this game. Complete or cancel it first.');
        }

        // Ensure player has a rating for this game
        $rating = $this->ensurePlayerRating($user, $match->game_id);

        // Add player via aggregate
        MatchAggregate::retrieve($match->uuid)
            ->addPlayer(
                userId: $user->id,
                ratingBefore: $rating->rating,
            )
            ->persist();

        return $match->fresh();
    }

    private function ensurePlayerRating(User $user, int $gameId): PlayerRating
    {
        return PlayerRating::firstOrCreate(
            [
                'user_id' => $user->id,
                'game_id' => $gameId,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'rating' => EloCalculator::defaultRating(),
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'win_streak' => 0,
                'best_rating' => EloCalculator::defaultRating(),
            ]
        );
    }
}
