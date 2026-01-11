<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\EventHasher;
use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

final class HashedStoredEvent extends EloquentStoredEvent
{
    /**
     * Check if this event's hash is valid.
     */
    public function isHashValid(): bool
    {
        if (! $this->event_hash) {
            return false;
        }

        $hasher = app(EventHasher::class);
        $computedHash = $hasher->computeHash(
            $this->id,
            $this->event_class,
            json_decode($this->event_properties, true),
            $this->created_at->toDateTimeString(),
            $this->previous_hash
        );

        return $this->event_hash === $computedHash;
    }

    protected static function booted(): void
    {
        parent::booted();

        self::created(function (HashedStoredEvent $event): void {
            // Use DB transaction to ensure atomicity
            DB::transaction(function () use ($event): void {
                $hasher = app(EventHasher::class);
                $hasher->hashEvent($event->id);
            });
        });
    }
}
