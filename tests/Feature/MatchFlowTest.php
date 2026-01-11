<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\PlayerRating;
use App\Models\User;

beforeEach(function () {
    // Create an admin user to pass EnsureSetupComplete middleware
    User::factory()->create(['is_admin' => true]);

    $this->user = User::factory()->create();
    $this->opponent = User::factory()->create();
    $this->game = Game::factory()->create(['name' => 'Pool', 'slug' => 'pool', 'is_active' => true]);
});

describe('Match Creation', function () {
    it('creates a quick match with default values', function () {
        $response = $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);

        $response->assertRedirect();

        $match = GameMatch::first();
        expect($match)->not->toBeNull();
        expect($match->match_type)->toBe('quick');
        expect($match->match_code)->toHaveLength(6);
        expect($match->share_token)->toHaveLength(32);
        expect($match->name)->toBeNull();
        expect($match->scheduled_at)->toBeNull();
        expect($match->status)->toBe('pending');

        $response->assertRedirect(route('matches.show', $match->uuid));
    });

    it('creates a quick match with a name', function () {
        $response = $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'match_type' => 'quick',
            'name' => 'Friday Pool Session',
        ]);

        $match = GameMatch::first();
        expect($match->match_type)->toBe('quick');
        expect($match->name)->toBe('Friday Pool Session');
        expect($match->scheduled_at)->toBeNull();
    });

    it('creates a booked match with scheduling', function () {
        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'match_type' => 'booked',
            'name' => 'Tournament Match',
            'scheduled_at' => $scheduledAt,
        ]);

        $match = GameMatch::first();
        expect($match->match_type)->toBe('booked');
        expect($match->name)->toBe('Tournament Match');
        expect($match->scheduled_at)->not->toBeNull();
    });

    it('validates scheduled_at is required for booked matches', function () {
        $response = $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'match_type' => 'booked',
        ]);

        $response->assertSessionHasErrors(['scheduled_at']);
    });

    it('validates scheduled_at must be in the future', function () {
        $pastDate = now()->subDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'match_type' => 'booked',
            'scheduled_at' => $pastDate,
        ]);

        $response->assertSessionHasErrors(['scheduled_at']);
    });

    it('generates unique share token for each match', function () {
        // Create first match
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);

        // User can't create another match for same game while one is pending
        // So we use a different user for the second match
        $anotherUser = User::factory()->create();
        $this->actingAs($anotherUser)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);

        $matches = GameMatch::all();
        expect($matches)->toHaveCount(2);
        expect($matches[0]->share_token)->not->toBe($matches[1]->share_token);
    });

    it('adds creator as first player', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);

        $match = GameMatch::first();
        expect($match->players)->toHaveCount(1);
        expect($match->players->first()->user_id)->toBe($this->user->id);
    });
});

describe('Join Match by Share Token', function () {
    it('allows user to join via share token', function () {
        // Create match first
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        // Opponent joins via share token
        $response = $this->actingAs($this->opponent)->get(route('matches.joinByShareToken', [
            'token' => $match->share_token,
        ]));

        $response->assertRedirect(route('matches.show', $match->uuid));
        expect($match->fresh()->players)->toHaveCount(2);
    });

    it('redirects to login if not authenticated', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        // Logout and try to join
        auth()->logout();
        $response = $this->get(route('matches.joinByShareToken', [
            'token' => $match->share_token,
        ]));

        $response->assertRedirect(route('login'));
    });

    it('returns error for invalid share token', function () {
        $response = $this->actingAs($this->opponent)->get(route('matches.joinByShareToken', [
            'token' => 'invalid-token-12345678901234',
        ]));

        $response->assertRedirect(route('matches.join'));
        $response->assertSessionHasErrors(['token']);
    });

    it('prevents joining own match via share token', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        $response = $this->actingAs($this->user)->get(route('matches.joinByShareToken', [
            'token' => $match->share_token,
        ]));

        $response->assertRedirect(route('matches.show', $match->uuid));
        $response->assertSessionHasErrors(['match']);
    });
});

describe('Cancel Match', function () {
    it('allows creator to cancel match when no opponent joined', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        $response = $this->actingAs($this->user)->post(route('matches.cancel', $match->uuid));

        $response->assertRedirect(route('dashboard'));
        expect($match->fresh()->status)->toBe('cancelled');
    });

    it('allows creator to cancel match after opponent joined', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        // Opponent joins
        $this->actingAs($this->opponent)->post(route('matches.joinByCode'), [
            'code' => $match->match_code,
        ]);

        // Creator cancels
        $response = $this->actingAs($this->user)->post(route('matches.cancel', $match->uuid));

        $response->assertRedirect(route('dashboard'));
        expect($match->fresh()->status)->toBe('cancelled');
    });

    it('allows participant to cancel pending match', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        // Opponent joins
        $this->actingAs($this->opponent)->post(route('matches.joinByCode'), [
            'code' => $match->match_code,
        ]);

        // Opponent cancels (not creator)
        $response = $this->actingAs($this->opponent)->post(route('matches.cancel', $match->uuid));

        $response->assertRedirect(route('dashboard'));
        expect($match->fresh()->status)->toBe('cancelled');
    });

    it('allows participant to cancel confirmed match', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        // Opponent joins
        $this->actingAs($this->opponent)->post(route('matches.joinByCode'), [
            'code' => $match->match_code,
        ]);

        // Confirm match
        $this->actingAs($this->user)->post(route('matches.confirm', $match->uuid));
        expect($match->fresh()->status)->toBe('confirmed');

        // Cancel confirmed match
        $response = $this->actingAs($this->user)->post(route('matches.cancel', $match->uuid));

        $response->assertRedirect(route('dashboard'));
        expect($match->fresh()->status)->toBe('cancelled');
    });

    it('prevents non-participant from cancelling match', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        $thirdUser = User::factory()->create();
        $response = $this->actingAs($thirdUser)->post(route('matches.cancel', $match->uuid));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['match']);
        expect($match->fresh()->status)->toBe('pending');
    });

    it('prevents cancelling completed match', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $match = GameMatch::first();

        // Opponent joins
        $this->actingAs($this->opponent)->post(route('matches.joinByCode'), [
            'code' => $match->match_code,
        ]);

        // Confirm and complete match
        $this->actingAs($this->user)->post(route('matches.confirm', $match->uuid));
        $this->actingAs($this->user)->post(route('matches.complete', $match->uuid), [
            'winner_id' => $this->user->id,
        ]);

        expect($match->fresh()->status)->toBe('completed');

        // Try to cancel
        $response = $this->actingAs($this->user)->post(route('matches.cancel', $match->uuid));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['match']);
        expect($match->fresh()->status)->toBe('completed');
    });
});

describe('Match Show Page', function () {
    it('displays match with share url', function () {
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'name' => 'Test Match',
        ]);
        $match = GameMatch::first();

        $response = $this->actingAs($this->user)->get(route('matches.show', $match->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Matches/Show')
            ->has('match')
            ->where('match.matchCode', $match->match_code)
            ->where('match.name', 'Test Match')
            ->where('match.matchType', 'quick')
            ->has('match.shareUrl')
        );
    });

    it('displays scheduled time for booked matches', function () {
        $scheduledAt = now()->addDay();

        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'match_type' => 'booked',
            'scheduled_at' => $scheduledAt->format('Y-m-d\TH:i'),
        ]);
        $match = GameMatch::first();

        $response = $this->actingAs($this->user)->get(route('matches.show', $match->uuid));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Matches/Show')
            ->where('match.matchType', 'booked')
            ->has('match.scheduledAt')
        );
    });
});

describe('Player Match History', function () {
    beforeEach(function () {
        // Create a completed match
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);
        $this->match = GameMatch::first();

        // Opponent joins
        $this->actingAs($this->opponent)->post(route('matches.joinByCode'), [
            'code' => $this->match->match_code,
        ]);

        // Confirm and complete
        $this->actingAs($this->user)->post(route('matches.confirm', $this->match->uuid));
        $this->actingAs($this->user)->post(route('matches.complete', $this->match->uuid), [
            'winner_id' => $this->user->id,
        ]);
    });

    it('shows player match history page', function () {
        $response = $this->get(route('leaderboard.player.matches', [
            'game' => $this->game->slug,
            'user' => $this->user->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Leaderboard/PlayerMatches')
            ->has('game')
            ->has('player')
            ->has('rating')
            ->has('matches.data', 1)
        );
    });

    it('shows opponent in match history', function () {
        $response = $this->get(route('leaderboard.player.matches', [
            'game' => $this->game->slug,
            'user' => $this->user->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('matches.data.0.players', 2)
        );
    });

    it('returns 404 for invalid game slug', function () {
        $response = $this->get(route('leaderboard.player.matches', [
            'game' => 'invalid-game',
            'user' => $this->user->id,
        ]));

        $response->assertNotFound();
    });

    it('returns 404 for invalid user id', function () {
        $response = $this->get(route('leaderboard.player.matches', [
            'game' => $this->game->slug,
            'user' => 99999,
        ]));

        $response->assertNotFound();
    });

    it('only shows completed matches', function () {
        // Create a pending match
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
        ]);

        $response = $this->get(route('leaderboard.player.matches', [
            'game' => $this->game->slug,
            'user' => $this->user->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('matches.data', 1) // Only the completed match
        );
    });

    it('paginates match history', function () {
        // Create more completed matches
        for ($i = 0; $i < 25; $i++) {
            $match = GameMatch::create([
                'uuid' => Illuminate\Support\Str::uuid(),
                'game_id' => $this->game->id,
                'match_code' => GameMatch::generateMatchCode(),
                'share_token' => GameMatch::generateShareToken(),
                'status' => 'completed',
                'created_by_user_id' => $this->user->id,
                'played_at' => now()->subDays($i),
            ]);

            MatchPlayer::create([
                'match_id' => $match->id,
                'user_id' => $this->user->id,
                'result' => 'win',
                'rating_before' => 1000,
                'rating_after' => 1016,
                'rating_change' => 16,
            ]);

            MatchPlayer::create([
                'match_id' => $match->id,
                'user_id' => $this->opponent->id,
                'result' => 'lose',
                'rating_before' => 1000,
                'rating_after' => 984,
                'rating_change' => -16,
            ]);
        }

        $response = $this->get(route('leaderboard.player.matches', [
            'game' => $this->game->slug,
            'user' => $this->user->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('matches.data', 20) // First page with 20 items
            ->where('matches.current_page', 1)
            ->where('matches.last_page', 2)
        );
    });
});

describe('Full Match Flow', function () {
    it('completes full quick match flow', function () {
        // 1. Create match
        $this->actingAs($this->user)->post(route('matches.store'), [
            'game_id' => $this->game->id,
            'name' => 'Quick Game',
        ]);
        $match = GameMatch::first();
        expect($match->status)->toBe('pending');
        expect($match->players)->toHaveCount(1);

        // 2. Opponent joins via share token
        $this->actingAs($this->opponent)->get(route('matches.joinByShareToken', [
            'token' => $match->share_token,
        ]));
        expect($match->fresh()->players)->toHaveCount(2);
        expect($match->fresh()->status)->toBe('pending');

        // 3. Confirm match
        $this->actingAs($this->user)->post(route('matches.confirm', $match->uuid));
        expect($match->fresh()->status)->toBe('confirmed');

        // 4. Complete match
        $this->actingAs($this->user)->post(route('matches.complete', $match->uuid), [
            'winner_id' => $this->user->id,
        ]);
        expect($match->fresh()->status)->toBe('completed');

        // 5. Verify ratings updated
        $userRating = PlayerRating::where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->first();
        $opponentRating = PlayerRating::where('user_id', $this->opponent->id)
            ->where('game_id', $this->game->id)
            ->first();

        expect($userRating->wins)->toBe(1);
        expect($opponentRating->losses)->toBe(1);
    });
});
