<?php

declare(strict_types=1);

namespace App\Events;

use App\Data\MatchData;
use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MatchUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public MatchData $match;

    public function __construct(
        public GameMatch $gameMatch,
        public string $action
    ) {
        $this->match = MatchData::fromModel(
            $gameMatch->load(['game', 'createdBy', 'players.user'])
        );
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('match.'.$this->gameMatch->uuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match' => $this->match->toArray(),
            'action' => $this->action,
        ];
    }
}
