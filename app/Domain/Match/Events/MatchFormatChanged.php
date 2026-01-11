<?php

declare(strict_types=1);

namespace App\Domain\Match\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MatchFormatChanged extends ShouldBeStored
{
    public function __construct(
        public readonly string $matchUuid,
        public readonly string $newFormat,
        public readonly int $newMaxPlayers,
    ) {}
}
