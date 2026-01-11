<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PlayerRating;
use App\Services\LeaderboardCacheService;

final class PlayerRatingObserver
{
    public function __construct(
        private readonly LeaderboardCacheService $cacheService
    ) {}

    /**
     * Handle the PlayerRating "created" event.
     */
    public function created(PlayerRating $playerRating): void
    {
        $this->cacheService->invalidate($playerRating->game_id);
    }

    /**
     * Handle the PlayerRating "updated" event.
     */
    public function updated(PlayerRating $playerRating): void
    {
        $this->cacheService->invalidate($playerRating->game_id);
    }

    /**
     * Handle the PlayerRating "deleted" event.
     */
    public function deleted(PlayerRating $playerRating): void
    {
        $this->cacheService->invalidate($playerRating->game_id);
    }
}
