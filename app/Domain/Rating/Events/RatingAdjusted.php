<?php

declare(strict_types=1);

namespace App\Domain\Rating\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class RatingAdjusted extends ShouldBeStored
{
    public function __construct(
        public readonly string $ratingUuid,
        public readonly int $userId,
        public readonly int $gameId,
        public readonly int $oldRating,
        public readonly int $newRating,
        public readonly int $change,
        public readonly string $matchUuid,
        public readonly bool $won,
    ) {}
}
