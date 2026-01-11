<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\LeaderboardEntryData;
use App\Models\Game;
use App\Models\PlayerRating;
use Illuminate\Support\Facades\Cache;

final class LeaderboardCacheService
{
    private const CACHE_PREFIX = 'leaderboard:';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the leaderboard for a game, using cache if available.
     *
     * @return array<LeaderboardEntryData>
     */
    public function getLeaderboard(Game $game): array
    {
        if (! $this->isRedisAvailable()) {
            return $this->fetchLeaderboard($game);
        }

        $cacheKey = $this->getCacheKey($game->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn () => $this->fetchLeaderboard($game));
    }

    /**
     * Invalidate the leaderboard cache for a specific game.
     */
    public function invalidate(int $gameId): void
    {
        if ($this->isRedisAvailable()) {
            Cache::forget($this->getCacheKey($gameId));
        }
    }

    /**
     * Invalidate the leaderboard cache for all games.
     */
    public function invalidateAll(): void
    {
        if (! $this->isRedisAvailable()) {
            return;
        }

        $games = Game::all();
        foreach ($games as $game) {
            Cache::forget($this->getCacheKey($game->id));
        }
    }

    /**
     * Fetch the leaderboard data from the database.
     *
     * @return array<LeaderboardEntryData>
     */
    private function fetchLeaderboard(Game $game): array
    {
        $ratings = PlayerRating::where('game_id', $game->id)
            ->where('matches_played', '>', 0)
            ->with('user')
            ->orderByDesc('rating')
            ->take(100)
            ->get();

        return $ratings->map(
            fn (PlayerRating $rating, $index): LeaderboardEntryData => LeaderboardEntryData::fromModel($rating, $index + 1)
        )->all();
    }

    /**
     * Check if Redis is available as the cache driver.
     */
    private function isRedisAvailable(): bool
    {
        return config('cache.default') === 'redis';
    }

    /**
     * Get the cache key for a game's leaderboard.
     */
    private function getCacheKey(int $gameId): string
    {
        return self::CACHE_PREFIX.$gameId;
    }
}
