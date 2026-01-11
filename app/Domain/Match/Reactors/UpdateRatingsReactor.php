<?php

declare(strict_types=1);

namespace App\Domain\Match\Reactors;

use App\Domain\Match\Events\MatchCompleted;
use App\Domain\Match\Events\MatchResultsRecorded;
use App\Models\GameMatch;
use App\Models\PlayerRating;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

final class UpdateRatingsReactor extends Reactor
{
    public function onMatchCompleted(MatchCompleted $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        // Update winner rating
        $this->updatePlayerRating(
            userId: $event->winnerId,
            gameId: $match->game_id,
            oldRating: $event->winnerRatingBefore,
            change: $event->winnerRatingChange,
            won: true,
        );

        // Update loser rating
        $this->updatePlayerRating(
            userId: $event->loserId,
            gameId: $match->game_id,
            oldRating: $event->loserRatingBefore,
            change: $event->loserRatingChange,
            won: false,
        );

        // Cache invalidation is handled by PlayerRatingObserver
    }

    public function onMatchResultsRecorded(MatchResultsRecorded $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        foreach ($event->playerResults as $result) {
            $won = $result['result'] === 'win';

            $this->updatePlayerRating(
                userId: $result['user_id'],
                gameId: $match->game_id,
                oldRating: $result['rating_before'],
                change: $result['rating_change'],
                won: $won,
            );
        }

        // Cache invalidation is handled by PlayerRatingObserver
    }

    private function updatePlayerRating(
        int $userId,
        int $gameId,
        int $oldRating,
        int $change,
        bool $won
    ): void {
        $rating = PlayerRating::where('user_id', $userId)
            ->where('game_id', $gameId)
            ->first();

        if (! $rating) {
            return;
        }

        $newRating = $oldRating + $change;

        // Update the rating projection
        $rating->update([
            'rating' => $newRating,
            'matches_played' => $rating->matches_played + 1,
            'wins' => $won ? $rating->wins + 1 : $rating->wins,
            'losses' => $won ? $rating->losses : $rating->losses + 1,
            'win_streak' => $won ? $rating->win_streak + 1 : 0,
            'best_rating' => max($rating->best_rating, $newRating),
        ]);
    }
}
