<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\PlayerRating;
use App\Models\User;

describe('Admin User Index', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('shows users page to admin users', function () {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users')
        );
    });

    it('denies access to non-admin users', function () {
        $response = $this->actingAs($this->user)->get(route('admin.users.index'));

        $response->assertForbidden();
    });

    it('denies access to guests', function () {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    });

    it('returns user data with correct structure', function () {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.0', fn ($user) => $user
                ->has('id')
                ->has('name')
                ->has('nickname')
                ->has('displayName')
                ->has('email')
                ->has('isAdmin')
                ->has('isDisabled')
                ->has('ratingsCount')
                ->has('matchesCount')
                ->has('createdAt')
            )
        );
    });
});

describe('Admin User Store', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    });

    it('allows admin to create a user', function () {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'is_admin' => false,
        ]);
    });

    it('allows admin to create an admin user', function () {
        $userData = [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'is_admin' => true,
        ]);
    });

    it('prevents non-admin from creating a user', function () {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->user)->post(route('admin.users.store'), $userData);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'newuser@example.com']);
    });

    it('validates required fields when creating a user', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    });

    it('validates unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates password confirmation', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['password']);
    });
});

describe('Admin User Update', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target@example.com',
        ]);
    });

    it('allows admin to update a user', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->targetUser), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    });

    it('allows admin to update user password', function () {
        $oldPassword = $this->targetUser->password;

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->targetUser), [
            'name' => $this->targetUser->name,
            'email' => $this->targetUser->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->targetUser->refresh();
        expect($this->targetUser->password)->not->toBe($oldPassword);
    });

    it('prevents non-admin from updating a user', function () {
        $response = $this->actingAs($this->user)->put(route('admin.users.update', $this->targetUser), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'name' => 'Target User',
        ]);
    });

    it('validates required fields when updating a user', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->targetUser), []);

        $response->assertSessionHasErrors(['name', 'email']);
    });

    it('allows same email for the same user', function () {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->targetUser), [
            'name' => 'Updated Name',
            'email' => $this->targetUser->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });
});

describe('Admin User Toggle Admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->targetUser = User::factory()->create(['is_admin' => false]);
    });

    it('allows admin to make a user admin', function () {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-admin', $this->targetUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'is_admin' => true,
        ]);
    });

    it('allows admin to remove admin status from another user', function () {
        $this->targetUser->update(['is_admin' => true]);

        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-admin', $this->targetUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'is_admin' => false,
        ]);
    });

    it('prevents admin from removing own admin status', function () {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-admin', $this->admin));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_admin' => true,
        ]);
    });

    it('prevents non-admin from toggling admin status', function () {
        $response = $this->actingAs($this->user)->patch(route('admin.users.toggle-admin', $this->targetUser));

        $response->assertForbidden();
    });
});

describe('Admin User Toggle Disabled', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->targetUser = User::factory()->create(['is_disabled' => false]);
    });

    it('allows admin to disable a user', function () {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-disabled', $this->targetUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'is_disabled' => true,
        ]);
    });

    it('allows admin to enable a disabled user', function () {
        $this->targetUser->update(['is_disabled' => true]);

        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-disabled', $this->targetUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'is_disabled' => false,
        ]);
    });

    it('prevents admin from disabling own account', function () {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-disabled', $this->admin));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_disabled' => false,
        ]);
    });

    it('prevents non-admin from toggling disabled status', function () {
        $response = $this->actingAs($this->user)->patch(route('admin.users.toggle-disabled', $this->targetUser));

        $response->assertForbidden();
    });
});

describe('Admin User Delete', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
        $this->targetUser = User::factory()->create();
        $this->game = Game::factory()->create();
    });

    it('allows admin to delete a user', function () {
        $userId = $this->targetUser->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->targetUser));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $userId]);
    });

    it('deletes user ratings when deleting user', function () {
        PlayerRating::factory()->create([
            'user_id' => $this->targetUser->id,
            'game_id' => $this->game->id,
        ]);

        $this->assertDatabaseHas('player_ratings', ['user_id' => $this->targetUser->id]);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->targetUser));

        $this->assertDatabaseMissing('player_ratings', ['user_id' => $this->targetUser->id]);
    });

    it('deletes user match participation when deleting user', function () {
        $match = GameMatch::factory()->create(['game_id' => $this->game->id]);
        MatchPlayer::factory()->create([
            'match_id' => $match->id,
            'user_id' => $this->targetUser->id,
        ]);

        $this->assertDatabaseHas('match_players', ['user_id' => $this->targetUser->id]);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->targetUser));

        $this->assertDatabaseMissing('match_players', ['user_id' => $this->targetUser->id]);
    });

    it('cancels pending matches when deleting user who created them', function () {
        $pendingMatch = GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'created_by_user_id' => $this->targetUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->targetUser));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $this->targetUser->id]);

        // Pending match should be cancelled
        $this->assertDatabaseHas('matches', [
            'id' => $pendingMatch->id,
            'status' => 'cancelled',
            'created_by_user_id' => $this->targetUser->id, // Reference kept
        ]);
    });

    it('keeps completed matches when deleting user who created them', function () {
        $completedMatch = GameMatch::factory()->completed()->create([
            'game_id' => $this->game->id,
            'created_by_user_id' => $this->targetUser->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->targetUser));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $this->targetUser->id]);

        // Completed match should remain unchanged
        $this->assertDatabaseHas('matches', [
            'id' => $completedMatch->id,
            'status' => 'completed',
            'created_by_user_id' => $this->targetUser->id, // Reference kept
        ]);
    });

    it('prevents admin from deleting own account', function () {
        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    });

    it('prevents non-admin from deleting a user', function () {
        $response = $this->actingAs($this->user)->delete(route('admin.users.destroy', $this->targetUser));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $this->targetUser->id]);
    });
});
