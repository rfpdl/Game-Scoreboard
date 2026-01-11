<?php

declare(strict_types=1);

use App\Events\MatchUpdated;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Broadcasting\Channel;

describe('MatchUpdated Event Structure', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->game = Game::factory()->create();
        $this->match = GameMatch::factory()->create([
            'game_id' => $this->game->id,
            'created_by_user_id' => $this->user->id,
            'uuid' => 'test-uuid-1234',
        ]);
    });

    it('broadcasts to the correct channel', function () {
        $event = new MatchUpdated($this->match, 'test_action');
        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1);
        expect($channels[0])->toBeInstanceOf(Channel::class);
        expect($channels[0]->name)->toBe('match.test-uuid-1234');
    });

    it('uses correct broadcast event name', function () {
        $event = new MatchUpdated($this->match, 'test_action');

        expect($event->broadcastAs())->toBe('match.updated');
    });

    it('includes match data in broadcast payload', function () {
        $event = new MatchUpdated($this->match, 'player_joined');
        $payload = $event->broadcastWith();

        expect($payload)->toHaveKeys(['match', 'action']);
        expect($payload['match'])->toBeArray();
        expect($payload['match']['uuid'])->toBe('test-uuid-1234');
    });

    it('includes action in broadcast payload', function () {
        $event = new MatchUpdated($this->match, 'player_joined');
        $payload = $event->broadcastWith();

        expect($payload['action'])->toBe('player_joined');
    });

    it('includes game data in match payload', function () {
        $event = new MatchUpdated($this->match->load('game'), 'test');
        $payload = $event->broadcastWith();

        // MatchData flattens game data into gameName, gameIcon, gameRules
        expect($payload['match'])->toHaveKeys(['gameName', 'gameIcon']);
        expect($payload['match']['gameName'])->toBe($this->game->name);
    });

    it('includes players data in match payload', function () {
        $this->match->players()->create([
            'user_id' => $this->user->id,
            'team' => 'A',
        ]);

        $event = new MatchUpdated($this->match->load('players.user'), 'test');
        $payload = $event->broadcastWith();

        expect($payload['match'])->toHaveKey('players');
        expect($payload['match']['players'])->toBeArray();
    });

    it('broadcasts different actions correctly', function () {
        $actions = ['player_joined', 'player_left', 'confirmed', 'started', 'completed', 'cancelled', 'team_switched', 'format_changed'];

        foreach ($actions as $action) {
            $event = new MatchUpdated($this->match, $action);
            $payload = $event->broadcastWith();

            expect($payload['action'])->toBe($action);
        }
    });
});

describe('MatchUpdated Event Channel Format', function () {
    it('uses public channel (not private)', function () {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $match = GameMatch::factory()->create([
            'game_id' => $game->id,
            'created_by_user_id' => $user->id,
        ]);

        $event = new MatchUpdated($match, 'test');
        $channels = $event->broadcastOn();

        // Should be a public Channel, not PrivateChannel
        expect($channels[0])->toBeInstanceOf(Channel::class);
        expect(get_class($channels[0]))->toBe(Channel::class);
    });

    it('channel name matches match UUID', function () {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $uuids = [
            'abc-123-def',
            '550e8400-e29b-41d4-a716-446655440000',
            'match-uuid-test',
        ];

        foreach ($uuids as $uuid) {
            $match = GameMatch::factory()->create([
                'game_id' => $game->id,
                'created_by_user_id' => $user->id,
                'uuid' => $uuid,
            ]);

            $event = new MatchUpdated($match, 'test');
            $channels = $event->broadcastOn();

            expect($channels[0]->name)->toBe("match.{$uuid}");
        }
    });
});

describe('MatchUpdated Implements ShouldBroadcastNow', function () {
    it('implements ShouldBroadcastNow interface', function () {
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $match = GameMatch::factory()->create([
            'game_id' => $game->id,
            'created_by_user_id' => $user->id,
        ]);

        $event = new MatchUpdated($match, 'test');

        expect($event)->toBeInstanceOf(Illuminate\Contracts\Broadcasting\ShouldBroadcastNow::class);
    });
});
