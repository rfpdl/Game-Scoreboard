<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Models\GameMatch;
use App\Models\PlayerRating;
use App\Models\User;
use DomainException;

final class CompleteMatchAction
{
    public function execute(GameMatch $match, User $winner): GameMatch
    {
        if (! $match->isConfirmed()) {
            throw new DomainException('Match must be confirmed to complete');
        }

        if (! $match->players()->where('user_id', $winner->id)->exists()) {
            throw new DomainException('Winner must be a participant in the match');
        }

        // Get player ratings for K-factor and streak calculations
        $players = $match->players()->with('user')->get();
        $loser = $players->firstWhere('user_id', '!=', $winner->id);

        $winnerRating = PlayerRating::where('user_id', $winner->id)
            ->where('game_id', $match->game_id)
            ->first();

        $loserRating = PlayerRating::where('user_id', $loser->user_id)
            ->where('game_id', $match->game_id)
            ->first();

        MatchAggregate::retrieve($match->uuid)
            ->completeMatch(
                winnerId: $winner->id,
                winnerMatchesPlayed: $winnerRating?->matches_played ?? 0,
                loserMatchesPlayed: $loserRating?->matches_played ?? 0,
                loserWinStreak: $loserRating?->win_streak ?? 0,
            )
            ->persist();

        return $match->fresh();
    }
}
