<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Models\GameMatch;
use App\Models\User;

final class SwitchTeamAction
{
    public function execute(GameMatch $match, User $user): void
    {
        MatchAggregate::retrieve($match->uuid)
            ->switchTeam($user->id)
            ->persist();
    }
}
