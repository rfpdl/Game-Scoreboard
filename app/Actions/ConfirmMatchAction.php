<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Models\GameMatch;
use DomainException;

final class ConfirmMatchAction
{
    public function execute(GameMatch $match): GameMatch
    {
        if (! $match->isPending()) {
            throw new DomainException('Match is not pending');
        }

        if ($match->players()->count() < 2) {
            throw new DomainException('Match needs 2 players to confirm');
        }

        MatchAggregate::retrieve($match->uuid)
            ->confirmMatch()
            ->persist();

        return $match->fresh();
    }
}
