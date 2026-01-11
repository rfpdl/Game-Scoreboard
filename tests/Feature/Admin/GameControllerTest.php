<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;

describe('Admin Games Index', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('shows games list to admin', function () {
        Game::factory()->create(['name' => 'Pool', 'slug' => 'pool']);
        Game::factory()->create(['name' => 'Darts', 'slug' => 'darts']);

        $response = $this->actingAs($this->admin)->get(route('admin.games.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Games/Index')
            ->has('games', 2)
        );
    });

    it('denies access to non-admin users', function () {
        $response = $this->actingAs($this->user)->get(route('admin.games.index'));

        $response->assertForbidden();
    });

    it('denies access to guests', function () {
        $response = $this->get(route('admin.games.index'));

        $response->assertRedirect(route('login'));
    });

    it('orders games by name', function () {
        Game::factory()->create(['name' => 'Zebra Game', 'slug' => 'zebra']);
        Game::factory()->create(['name' => 'Alpha Game', 'slug' => 'alpha']);

        $response = $this->actingAs($this->admin)->get(route('admin.games.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('games.0.name', 'Alpha Game')
            ->where('games.1.name', 'Zebra Game')
        );
    });
});

describe('Admin Games Create', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('shows create form to admin', function () {
        $response = $this->actingAs($this->admin)->get(route('admin.games.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Games/Create'));
    });

    it('denies access to non-admin', function () {
        $response = $this->actingAs($this->user)->get(route('admin.games.create'));

        $response->assertForbidden();
    });
});

describe('Admin Games Store', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('creates a new game', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'Foosball',
            'slug' => 'foosball',
            'description' => 'Table football game',
            'rules' => "Rule 1\nRule 2\nRule 3",
            'icon' => '⚽',
            'min_players' => 2,
            'max_players' => 4,
        ]);

        $response->assertRedirect(route('admin.games.index'));
        $response->assertSessionHas('success');

        $game = Game::where('slug', 'foosball')->first();
        expect($game)->not->toBeNull();
        expect($game->name)->toBe('Foosball');
        expect($game->description)->toBe('Table football game');
        expect($game->rules)->toBe(['Rule 1', 'Rule 2', 'Rule 3']);
        expect($game->icon)->toBe('⚽');
        expect($game->min_players)->toBe(2);
        expect($game->max_players)->toBe(4);
        expect($game->is_active)->toBeTrue();
    });

    it('creates game with minimal data', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'Simple Game',
            'slug' => 'simple-game',
        ]);

        $response->assertRedirect(route('admin.games.index'));

        $game = Game::where('slug', 'simple-game')->first();
        expect($game)->not->toBeNull();
        expect($game->description)->toBeNull();
        expect($game->rules)->toBeNull();
    });

    it('denies non-admin from creating games', function () {
        $response = $this->actingAs($this->user)->post(route('admin.games.store'), [
            'name' => 'Hacked Game',
            'slug' => 'hacked',
        ]);

        $response->assertForbidden();
        expect(Game::where('slug', 'hacked')->exists())->toBeFalse();
    });

    it('validates name is required', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'slug' => 'test-game',
        ]);

        $response->assertSessionHasErrors(['name']);
    });

    it('validates slug is required', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'Test Game',
        ]);

        $response->assertSessionHasErrors(['slug']);
    });

    it('validates slug is unique', function () {
        Game::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'New Game',
            'slug' => 'existing-slug',
        ]);

        $response->assertSessionHasErrors(['slug']);
    });

    it('validates min_players range', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'Test Game',
            'slug' => 'test-game',
            'min_players' => 1, // Below minimum of 2
        ]);

        $response->assertSessionHasErrors(['min_players']);
    });

    it('validates max_players range', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'Test Game',
            'slug' => 'test-game',
            'max_players' => 15, // Above maximum of 10
        ]);

        $response->assertSessionHasErrors(['max_players']);
    });

    it('logs activity when game is created', function () {
        $this->actingAs($this->admin)->post(route('admin.games.store'), [
            'name' => 'Logged Game',
            'slug' => 'logged-game',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'created',
            'subject_type' => 'game',
            'subject_name' => 'Logged Game',
        ]);
    });
});

describe('Admin Games Edit', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->game = Game::factory()->create(['name' => 'Pool', 'slug' => 'pool']);
    });

    it('shows edit form to admin', function () {
        $response = $this->actingAs($this->admin)->get(route('admin.games.edit', $this->game));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Games/Edit')
            ->has('game', fn ($game) => $game
                ->where('name', 'Pool')
                ->where('slug', 'pool')
                ->etc()
            )
        );
    });

    it('denies access to non-admin', function () {
        $response = $this->actingAs($this->user)->get(route('admin.games.edit', $this->game));

        $response->assertForbidden();
    });
});

describe('Admin Games Update', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->game = Game::factory()->create([
            'name' => 'Pool',
            'slug' => 'pool',
            'description' => 'Original description',
        ]);
    });

    it('updates game successfully', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.games.update', $this->game), [
            'name' => 'Updated Pool',
            'slug' => 'updated-pool',
            'description' => 'New description',
            'rules' => "New Rule 1\nNew Rule 2",
            'icon' => '🎱',
            'min_players' => 2,
            'max_players' => 4,
        ]);

        $response->assertRedirect(route('admin.games.index'));
        $response->assertSessionHas('success');

        $this->game->refresh();
        expect($this->game->name)->toBe('Updated Pool');
        expect($this->game->slug)->toBe('updated-pool');
        expect($this->game->description)->toBe('New description');
        expect($this->game->rules)->toBe(['New Rule 1', 'New Rule 2']);
    });

    it('allows keeping the same slug', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.games.update', $this->game), [
            'name' => 'Updated Pool',
            'slug' => 'pool', // Same slug
            'min_players' => 2,
            'max_players' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });

    it('prevents using another games slug', function () {
        Game::factory()->create(['slug' => 'darts']);

        $response = $this->actingAs($this->admin)->put(route('admin.games.update', $this->game), [
            'name' => 'Updated Pool',
            'slug' => 'darts', // Already exists
            'min_players' => 2,
            'max_players' => 2,
        ]);

        $response->assertSessionHasErrors(['slug']);
    });

    it('denies non-admin from updating', function () {
        $response = $this->actingAs($this->user)->put(route('admin.games.update', $this->game), [
            'name' => 'Hacked Name',
            'slug' => 'pool',
            'min_players' => 2,
            'max_players' => 2,
        ]);

        $response->assertForbidden();

        $this->game->refresh();
        expect($this->game->name)->toBe('Pool');
    });
});

describe('Admin Games Toggle', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('toggles game from active to inactive', function () {
        $game = Game::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->patch(route('admin.games.toggle', $game));

        $response->assertRedirect();

        $game->refresh();
        expect($game->is_active)->toBeFalse();
    });

    it('toggles game from inactive to active', function () {
        $game = Game::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->patch(route('admin.games.toggle', $game));

        $response->assertRedirect();

        $game->refresh();
        expect($game->is_active)->toBeTrue();
    });

    it('denies non-admin from toggling', function () {
        $game = Game::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->patch(route('admin.games.toggle', $game));

        $response->assertForbidden();

        $game->refresh();
        expect($game->is_active)->toBeTrue();
    });
});

describe('Admin Games Delete', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('deletes a game', function () {
        $game = Game::factory()->create(['slug' => 'to-delete']);

        $response = $this->actingAs($this->admin)->delete(route('admin.games.destroy', $game));

        $response->assertRedirect(route('admin.games.index'));
        $response->assertSessionHas('success');
        expect(Game::where('slug', 'to-delete')->exists())->toBeFalse();
    });

    it('denies non-admin from deleting', function () {
        $game = Game::factory()->create(['slug' => 'protected']);

        $response = $this->actingAs($this->user)->delete(route('admin.games.destroy', $game));

        $response->assertForbidden();
        expect(Game::where('slug', 'protected')->exists())->toBeTrue();
    });
});
