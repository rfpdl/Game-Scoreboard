<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

describe('Game Index', function () {
    beforeEach(function () {
        // Create admin to pass EnsureSetupComplete middleware
        User::factory()->create(['is_admin' => true]);
    });

    it('shows games page to authenticated users', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $game = Game::factory()->create(['name' => 'Pool', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('games.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Games/Index')
            ->has('games', 1)
        );
    });

    it('shows all games including inactive for admin', function () {
        $admin = User::factory()->create(['is_admin' => true]);
        Game::factory()->create(['name' => 'Pool', 'is_active' => true]);
        Game::factory()->create(['name' => 'Darts', 'is_active' => false]);

        $response = $this->actingAs($admin)->get(route('games.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Games/Index')
            ->has('games', 2)
        );
    });

    it('only shows active games for regular users', function () {
        $user = User::factory()->create(['is_admin' => false]);
        Game::factory()->create(['name' => 'Pool', 'is_active' => true]);
        Game::factory()->create(['name' => 'Darts', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('games.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Games/Index')
            ->has('games', 1)
        );
    });
});

describe('Create Game', function () {
    beforeEach(function () {
        // Create admin to pass EnsureSetupComplete middleware
        $this->admin = User::factory()->create(['is_admin' => true]);
    });

    it('allows admin to create a game', function () {
        $gameData = [
            'name' => 'Pool',
            'slug' => 'pool',
            'description' => 'Classic 8-ball pool',
            'icon' => '8',
            'min_players' => 2,
            'max_players' => 2,
        ];

        $response = $this->actingAs($this->admin)->post(route('games.store'), $gameData);

        $response->assertRedirect(route('games.index'));
        $this->assertDatabaseHas('games', [
            'name' => 'Pool',
            'slug' => 'pool',
            'is_active' => true,
        ]);
    });

    it('prevents non-admin from creating a game', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $gameData = [
            'name' => 'Pool',
            'slug' => 'pool',
            'min_players' => 2,
            'max_players' => 2,
        ];

        $response = $this->actingAs($user)->post(route('games.store'), $gameData);

        $response->assertForbidden();
        $this->assertDatabaseMissing('games', ['name' => 'Pool']);
    });

    it('validates required fields when creating a game', function () {
        $response = $this->actingAs($this->admin)->post(route('games.store'), []);

        $response->assertSessionHasErrors(['name', 'slug']);
    });

    it('validates unique slug', function () {
        Game::factory()->create(['slug' => 'pool']);

        $response = $this->actingAs($this->admin)->post(route('games.store'), [
            'name' => 'Pool 2',
            'slug' => 'pool',
            'min_players' => 2,
            'max_players' => 2,
        ]);

        $response->assertSessionHasErrors(['slug']);
    });
});

describe('Update Game', function () {
    beforeEach(function () {
        // Create admin to pass EnsureSetupComplete middleware
        $this->admin = User::factory()->create(['is_admin' => true]);
    });

    it('allows admin to update a game', function () {
        $game = Game::factory()->create(['name' => 'Pool', 'slug' => 'pool']);

        $response = $this->actingAs($this->admin)->put(route('games.update', $game), [
            'name' => 'Pool Updated',
            'slug' => 'pool',
            'description' => 'Updated description',
            'min_players' => 2,
            'max_players' => 4,
        ]);

        $response->assertRedirect(route('games.index'));
        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'name' => 'Pool Updated',
            'description' => 'Updated description',
            'max_players' => 4,
        ]);
    });

    it('prevents non-admin from updating a game', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $game = Game::factory()->create(['name' => 'Pool']);

        $response = $this->actingAs($user)->put(route('games.update', $game), [
            'name' => 'Pool Updated',
            'slug' => 'pool-updated',
            'min_players' => 2,
            'max_players' => 2,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('games', ['id' => $game->id, 'name' => 'Pool']);
    });
});

describe('Toggle Game Active Status', function () {
    beforeEach(function () {
        // Create admin to pass EnsureSetupComplete middleware
        $this->admin = User::factory()->create(['is_admin' => true]);
    });

    it('allows admin to toggle game active status', function () {
        $game = Game::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->patch(route('games.toggle', $game));

        $response->assertRedirect(route('games.index'));
        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'is_active' => false,
        ]);
    });

    it('prevents non-admin from toggling game status', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $game = Game::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->patch(route('games.toggle', $game));

        $response->assertForbidden();
        $this->assertDatabaseHas('games', ['id' => $game->id, 'is_active' => true]);
    });
});

describe('Delete Game', function () {
    beforeEach(function () {
        // Create admin to pass EnsureSetupComplete middleware
        $this->admin = User::factory()->create(['is_admin' => true]);
    });

    it('allows admin to delete a game', function () {
        $game = Game::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('games.destroy', $game));

        $response->assertRedirect(route('games.index'));
        $this->assertSoftDeleted('games', ['id' => $game->id]);
    });

    it('prevents non-admin from deleting a game', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $game = Game::factory()->create();

        $response = $this->actingAs($user)->delete(route('games.destroy', $game));

        $response->assertForbidden();
        $this->assertDatabaseHas('games', ['id' => $game->id]);
    });
});
