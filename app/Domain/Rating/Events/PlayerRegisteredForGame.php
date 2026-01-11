<?php

declare(strict_types=1);

namespace App\Domain\Rating\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PlayerRegisteredForGame extends ShouldBeStored
{
    public function __construct(
        public readonly string $ratingUuid,
        public readonly int $userId,
        public readonly int $gameId,
        public readonly int $initialRating,
    ) {}
}
