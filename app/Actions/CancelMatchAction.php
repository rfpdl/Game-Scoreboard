<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Models\GameMatch;
use App\Models\User;
use DomainException;

final class CancelMatchAction
{
    public function execute(GameMatch $match, User $user, ?string $reason = null): void
    {
        // Check if user is a participant (creator or player)
        $isCreator = $match->created_by_user_id === $user->id;
        $isPlayer = $match->players()->where('user_id', $user->id)->exists();

        if (! $isCreator && ! $isPlayer) {
            throw new DomainException('Only match participants can cancel the match');
        }

        // Can cancel pending or confirmed matches (not completed or already cancelled)
        if ($match->status === 'completed') {
            throw new DomainException('Cannot cancel a completed match');
        }

        if ($match->status === 'cancelled') {
            throw new DomainException('Match is already cancelled');
        }

        MatchAggregate::retrieve($match->uuid)
            ->cancelMatch($reason)
            ->persist();
    }
}
