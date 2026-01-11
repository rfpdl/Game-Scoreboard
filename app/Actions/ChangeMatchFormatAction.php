<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Models\GameMatch;

final class ChangeMatchFormatAction
{
    public function execute(GameMatch $match, string $newFormat): void
    {
        // Determine max players based on format
        $maxPlayers = match ($newFormat) {
            '1v1' => 2,
            '2v2' => 4,
            '3v3' => 6,
            '4v4' => 8,
            'ffa' => $match->max_players, // Keep current for FFA, or default to 8
            default => 2,
        };

        // For FFA, default to at least 3 players
        if ($newFormat === 'ffa' && $maxPlayers < 3) {
            $maxPlayers = 8;
        }

        MatchAggregate::retrieve($match->uuid)
            ->changeFormat($newFormat, $maxPlayers)
            ->persist();
    }
}
