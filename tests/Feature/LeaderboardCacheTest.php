<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\PlayerRating;
use App\Models\User;
use App\Services\LeaderboardCacheService;
use Illuminate\Support\Facades\Cache;

describe('LeaderboardCacheService', function () {
    beforeEach(function () {
        $this->game = Game::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create();
        $this->cacheService = app(LeaderboardCacheService::class);
    });

    it('returns leaderboard data', function () {
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);

        $leaderboard = $this->cacheService->getLeaderboard($this->game);

        expect($leaderboard)->toBeArray();
        expect($leaderboard)->toHaveCount(1);
        expect($leaderboard[0]->rating)->toBe(1500);
    });

    it('excludes players with zero matches', function () {
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'rating' => 1000,
            'matches_played' => 0,
        ]);

        $leaderboard = $this->cacheService->getLeaderboard($this->game);

        expect($leaderboard)->toBeArray();
        expect($leaderboard)->toBeEmpty();
    });

    it('orders by rating descending', function () {
        $users = User::factory()->count(3)->create();

        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $users[0]->id,
            'rating' => 1200,
            'matches_played' => 5,
        ]);
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $users[1]->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $users[2]->id,
            'rating' => 1100,
            'matches_played' => 3,
        ]);

        $leaderboard = $this->cacheService->getLeaderboard($this->game);

        expect($leaderboard)->toHaveCount(3);
        expect($leaderboard[0]->rating)->toBe(1500);
        expect($leaderboard[1]->rating)->toBe(1200);
        expect($leaderboard[2]->rating)->toBe(1100);
    });

    it('assigns correct ranks', function () {
        $users = User::factory()->count(2)->create();

        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $users[0]->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $users[1]->id,
            'rating' => 1200,
            'matches_played' => 5,
        ]);

        $leaderboard = $this->cacheService->getLeaderboard($this->game);

        expect($leaderboard[0]->rank)->toBe(1);
        expect($leaderboard[1]->rank)->toBe(2);
    });

    it('limits to 100 entries', function () {
        $users = User::factory()->count(105)->create();

        foreach ($users as $index => $user) {
            PlayerRating::factory()->create([
                'game_id' => $this->game->id,
                'user_id' => $user->id,
                'rating' => 2000 - $index,
                'matches_played' => 1,
            ]);
        }

        $leaderboard = $this->cacheService->getLeaderboard($this->game);

        expect($leaderboard)->toHaveCount(100);
    });
});

describe('PlayerRatingObserver cache invalidation', function () {
    beforeEach(function () {
        // Use Redis cache for these tests
        config(['cache.default' => 'redis']);

        $this->game = Game::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create();
        $this->cacheService = app(LeaderboardCacheService::class);
    });

    it('invalidates cache when player rating is created', function () {
        // Pre-populate cache
        $this->cacheService->getLeaderboard($this->game);

        // Verify cache exists
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeTrue();

        // Create a new player rating (triggers observer)
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);

        // Cache should be invalidated
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeFalse();
    });

    it('invalidates cache when player rating is updated', function () {
        $rating = PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);

        // Pre-populate cache
        $this->cacheService->getLeaderboard($this->game);

        // Verify cache exists
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeTrue();

        // Update the rating (triggers observer)
        $rating->update(['rating' => 1600]);

        // Cache should be invalidated
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeFalse();
    });

    it('invalidates cache when player rating is deleted', function () {
        $rating = PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);

        // Pre-populate cache
        $this->cacheService->getLeaderboard($this->game);

        // Verify cache exists
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeTrue();

        // Delete the rating (triggers observer)
        $rating->delete();

        // Cache should be invalidated
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeFalse();
    });
});

describe('GameObserver cache invalidation', function () {
    beforeEach(function () {
        // Use Redis cache for these tests
        config(['cache.default' => 'redis']);

        $this->game = Game::factory()->create(['is_active' => true]);
        $this->cacheService = app(LeaderboardCacheService::class);
    });

    it('invalidates cache when game is updated', function () {
        // Pre-populate cache
        $this->cacheService->getLeaderboard($this->game);

        // Verify cache exists
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeTrue();

        // Update the game (triggers observer)
        $this->game->update(['name' => 'Updated Name']);

        // Cache should be invalidated
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeFalse();
    });

    it('invalidates cache when game is toggled', function () {
        // Pre-populate cache
        $this->cacheService->getLeaderboard($this->game);

        // Verify cache exists
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeTrue();

        // Toggle game active status (triggers observer)
        $this->game->update(['is_active' => false]);

        // Cache should be invalidated
        expect(Cache::has('leaderboard:'.$this->game->id))->toBeFalse();
    });

    it('invalidates cache when game is deleted', function () {
        $gameId = $this->game->id;

        // Pre-populate cache
        $this->cacheService->getLeaderboard($this->game);

        // Verify cache exists
        expect(Cache::has('leaderboard:'.$gameId))->toBeTrue();

        // Delete the game (triggers observer)
        $this->game->delete();

        // Cache should be invalidated
        expect(Cache::has('leaderboard:'.$gameId))->toBeFalse();
    });
});

describe('LeaderboardController cache integration', function () {
    beforeEach(function () {
        // Create an admin user to bypass the install wizard redirect
        User::factory()->create(['is_admin' => true]);

        $this->game = Game::factory()->create([
            'is_active' => true,
            'slug' => 'test-game',
        ]);
    });

    it('returns leaderboard data', function () {
        $user = User::factory()->create();
        PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $user->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);

        $response = $this->get(route('leaderboard.index', ['game' => $this->game->slug]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Leaderboard/Index')
            ->has('entries', 1)
            ->where('entries.0.rating', 1500)
        );
    });

    it('returns updated data after rating change', function () {
        $user = User::factory()->create();
        $rating = PlayerRating::factory()->create([
            'game_id' => $this->game->id,
            'user_id' => $user->id,
            'rating' => 1500,
            'matches_played' => 10,
        ]);

        // First request
        $response1 = $this->get(route('leaderboard.index', ['game' => $this->game->slug]));
        $response1->assertInertia(fn ($page) => $page->where('entries.0.rating', 1500));

        // Update rating (will trigger observer to invalidate cache)
        $rating->update(['rating' => 1600]);

        // Second request should show updated data
        $response2 = $this->get(route('leaderboard.index', ['game' => $this->game->slug]));
        $response2->assertInertia(fn ($page) => $page->where('entries.0.rating', 1600));
    });
});
