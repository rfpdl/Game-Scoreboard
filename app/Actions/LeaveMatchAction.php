<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Models\GameMatch;
use App\Models\User;
use DomainException;

final class LeaveMatchAction
{
    public function execute(GameMatch $match, User $user): void
    {
        // Check if user is a player (not creator)
        $isPlayer = $match->players()->where('user_id', $user->id)->exists();
        $isCreator = $match->created_by_user_id === $user->id;

        if (! $isPlayer) {
            throw new DomainException('You are not a participant in this match');
        }

        if ($isCreator) {
            throw new DomainException('Match creator cannot leave. Cancel the match instead.');
        }

        if ($match->status !== 'pending') {
            throw new DomainException('Cannot leave a match that is not pending');
        }

        MatchAggregate::retrieve($match->uuid)
            ->removePlayer($user->id)
            ->persist();
    }
}
