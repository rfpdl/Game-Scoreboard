<?php

declare(strict_types=1);

namespace App\Domain\Rating\Projectors;

use App\Domain\Rating\Events\PlayerRegisteredForGame;
use App\Models\PlayerRating;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class PlayerRatingProjector extends Projector
{
    public function onPlayerRegisteredForGame(PlayerRegisteredForGame $event): void
    {
        PlayerRating::create([
            'uuid' => $event->ratingUuid,
            'user_id' => $event->userId,
            'game_id' => $event->gameId,
            'rating' => $event->initialRating,
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_streak' => 0,
            'best_rating' => $event->initialRating,
        ]);
    }
}
