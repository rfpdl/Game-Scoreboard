<?php

declare(strict_types=1);

namespace App\Domain\Match\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MatchCompleted extends ShouldBeStored
{
    public function __construct(
        public readonly string $matchUuid,
        public readonly int $winnerId,
        public readonly int $loserId,
        public readonly int $winnerRatingBefore,
        public readonly int $loserRatingBefore,
        public readonly int $winnerRatingChange,
        public readonly int $loserRatingChange,
    ) {}
}
