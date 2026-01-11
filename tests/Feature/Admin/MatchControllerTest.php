<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\User;

describe('Admin Match Index', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->game = Game::factory()->create(['name' => 'Pool', 'is_active' => true]);
    });

    it('shows matches page to admin users', function () {
        $match = GameMatch::factory()->create(['game_id' => $this->game->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 1)
            ->has('games')
            ->has('users')
            ->has('filters')
        );
    });

    it('denies access to non-admin users', function () {
        $response = $this->actingAs($this->user)->get(route('admin.matches.index'));

        $response->assertForbidden();
    });

    it('denies access to guests', function () {
        $response = $this->get(route('admin.matches.index'));

        $response->assertRedirect(route('login'));
    });

    it('filters matches by status', function () {
        GameMatch::factory()->create(['game_id' => $this->game->id, 'status' => 'pending']);
        GameMatch::factory()->create(['game_id' => $this->game->id, 'status' => 'completed']);
        GameMatch::factory()->create(['game_id' => $this->game->id, 'status' => 'cancelled']);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index', ['status' => 'completed']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 1)
            ->where('filters.status', 'completed')
        );
    });

    it('filters matches by game', function () {
        $game2 = Game::factory()->create(['name' => 'Darts']);
        GameMatch::factory()->create(['game_id' => $this->game->id]);
        GameMatch::factory()->create(['game_id' => $this->game->id]);
        GameMatch::factory()->create(['game_id' => $game2->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index', ['game' => $this->game->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 2)
            ->where('filters.game', (string) $this->game->id)
        );
    });

    it('filters matches by user/player', function () {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();

        $match1 = GameMatch::factory()->create(['game_id' => $this->game->id]);
        MatchPlayer::factory()->create(['match_id' => $match1->id, 'user_id' => $player1->id]);

        $match2 = GameMatch::factory()->create(['game_id' => $this->game->id]);
        MatchPlayer::factory()->create(['match_id' => $match2->id, 'user_id' => $player2->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index', ['user' => $player1->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 1)
            ->where('filters.user', $player1->id)
        );
    });

    it('filters matches by date range', function () {
        GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'created_at' => now()->subDays(10),
        ]);
        GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'created_at' => now()->subDays(5),
        ]);
        GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index', [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->subDays(3)->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 1)
        );
    });

    it('combines multiple filters', function () {
        $player = User::factory()->create();

        // Match that matches all filters
        $match1 = GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);
        MatchPlayer::factory()->create(['match_id' => $match1->id, 'user_id' => $player->id]);

        // Match with wrong status
        $match2 = GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        MatchPlayer::factory()->create(['match_id' => $match2->id, 'user_id' => $player->id]);

        // Match with wrong player
        $match3 = GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index', [
            'status' => 'completed',
            'game' => $this->game->id,
            'user' => $player->id,
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 1)
        );
    });

    it('paginates results', function () {
        GameMatch::factory()->count(25)->create(['game_id' => $this->game->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data', 20) // 20 per page
            ->has('matches.links')
        );
    });

    it('returns match data with correct structure', function () {
        $match = GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'status' => 'completed',
        ]);
        $player = User::factory()->create(['name' => 'Test Player']);
        MatchPlayer::factory()->create([
            'match_id' => $match->id,
            'user_id' => $player->id,
            'result' => 'win',
            'rating_before' => 1500,
            'rating_after' => 1520,
            'rating_change' => 20,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.matches.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Matches/Index')
            ->has('matches.data.0', fn ($match) => $match
                ->has('uuid')
                ->has('code')
                ->has('status')
                ->has('format')
                ->has('game')
                ->has('players')
                ->has('createdAt')
                ->has('completedAt')
            )
        );
    });
});
