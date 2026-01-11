<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Game;
use App\Services\LeaderboardCacheService;

final class GameObserver
{
    public function __construct(
        private readonly LeaderboardCacheService $cacheService
    ) {}

    /**
     * Handle the Game "updated" event.
     */
    public function updated(Game $game): void
    {
        $this->cacheService->invalidate($game->id);
    }

    /**
     * Handle the Game "deleted" event.
     */
    public function deleted(Game $game): void
    {
        $this->cacheService->invalidate($game->id);
    }
}
