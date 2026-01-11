<?php

declare(strict_types=1);

namespace App\Domain\Match\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MatchCreated extends ShouldBeStored
{
    public function __construct(
        public readonly string $matchUuid,
        public readonly int $gameId,
        public readonly string $matchCode,
        public readonly int $createdByUserId,
        public readonly string $matchType = 'quick',
        public readonly ?string $name = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?string $shareToken = null,
        public readonly string $matchFormat = '1v1',
        public readonly int $maxPlayers = 2,
    ) {}
}
