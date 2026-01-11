<?php

declare(strict_types=1);

namespace App\Domain\Match\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PlayerJoinedMatch extends ShouldBeStored
{
    public function __construct(
        public readonly string $matchUuid,
        public readonly int $userId,
        public readonly int $ratingBefore,
        public readonly ?string $team = null,
    ) {}
}
