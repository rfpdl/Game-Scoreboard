<?php

declare(strict_types=1);

namespace App\Domain\Match\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Event for recording match results that supports all match formats:
 * - 1v1: Single winner and loser
 * - 2v2: Winning team and losing team
 * - FFA: All players ranked by placement
 */
final class MatchResultsRecorded extends ShouldBeStored
{
    /**
     * @param  string  $matchFormat  '1v1', '2v2', or 'ffa'
     * @param array<int, array{
     *     user_id: int,
     *     result: string,
     *     placement: int|null,
     *     rating_before: int,
     *     rating_change: int,
     *     team: string|null
     * }> $playerResults Array of player results indexed by user_id
     * @param  string|null  $winningTeam  For 2v2: 'team_a' or 'team_b'
     */
    public function __construct(
        public readonly string $matchUuid,
        public readonly string $matchFormat,
        public readonly array $playerResults,
        public readonly ?string $winningTeam = null,
    ) {}
}
