<?php

declare(strict_types=1);

namespace App\Domain\Match\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MatchCancelled extends ShouldBeStored
{
    public function __construct(
        public readonly string $matchUuid,
        public readonly ?string $reason = null,
    ) {}
}
