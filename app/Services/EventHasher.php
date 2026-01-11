<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class EventHasher
{
    private const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Compute SHA-256 hash for an event.
     */
    public function computeHash(
        int $eventId,
        string $eventClass,
        array $eventProperties,
        string $createdAt,
        string $previousHash
    ): string {
        $payload = json_encode([
            'id' => $eventId,
            'event_class' => $eventClass,
            'event_properties' => $eventProperties,
            'created_at' => $createdAt,
            'previous_hash' => $previousHash,
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', $payload);
    }

    /**
     * Get the hash of the last event in the chain.
     */
    public function getLastHash(): string
    {
        $lastEvent = DB::table('stored_events')
            ->orderByDesc('id')
            ->first(['event_hash']);

        return $lastEvent?->event_hash ?? self::GENESIS_HASH;
    }

    /**
     * Hash a newly stored event and update the database.
     */
    public function hashEvent(int $eventId): void
    {
        $event = DB::table('stored_events')->find($eventId);

        if (! $event || $event->event_hash !== null) {
            return;
        }

        // Get previous event's hash
        $previousEvent = DB::table('stored_events')
            ->where('id', '<', $eventId)
            ->orderByDesc('id')
            ->first(['event_hash']);

        $previousHash = $previousEvent?->event_hash ?? self::GENESIS_HASH;

        // Compute hash
        $eventProperties = json_decode((string) $event->event_properties, true);
        $hash = $this->computeHash(
            $eventId,
            $event->event_class,
            $eventProperties,
            $event->created_at,
            $previousHash
        );

        // Update the event with hash
        DB::table('stored_events')
            ->where('id', $eventId)
            ->update([
                'event_hash' => $hash,
                'previous_hash' => $previousHash,
            ]);
    }

    /**
     * Verify the integrity of the entire event chain.
     * Returns array of broken links, or empty array if chain is valid.
     */
    public function verifyChain(): array
    {
        $brokenLinks = [];
        $previousHash = self::GENESIS_HASH;

        $events = DB::table('stored_events')
            ->orderBy('id')
            ->get(['id', 'event_class', 'event_properties', 'created_at', 'event_hash', 'previous_hash']);

        foreach ($events as $event) {
            // Check if previous_hash matches
            if ($event->previous_hash !== $previousHash) {
                $brokenLinks[] = [
                    'event_id' => $event->id,
                    'error' => 'Previous hash mismatch',
                    'expected' => $previousHash,
                    'actual' => $event->previous_hash,
                ];
            }

            // Recompute hash and verify
            $eventProperties = json_decode((string) $event->event_properties, true);
            $computedHash = $this->computeHash(
                $event->id,
                $event->event_class,
                $eventProperties,
                $event->created_at,
                $event->previous_hash
            );

            if ($event->event_hash !== $computedHash) {
                $brokenLinks[] = [
                    'event_id' => $event->id,
                    'error' => 'Event hash mismatch (data tampered)',
                    'expected' => $computedHash,
                    'actual' => $event->event_hash,
                ];
            }

            $previousHash = $event->event_hash;
        }

        return $brokenLinks;
    }

    /**
     * Hash all unhashed events (for migration of existing data).
     */
    public function hashAllUnhashed(): int
    {
        $count = 0;
        $previousHash = self::GENESIS_HASH;

        // Get the last hashed event
        $lastHashed = DB::table('stored_events')
            ->whereNotNull('event_hash')
            ->orderByDesc('id')
            ->first(['id', 'event_hash']);

        if ($lastHashed) {
            $previousHash = $lastHashed->event_hash;
        }

        // Get all unhashed events after the last hashed one
        $query = DB::table('stored_events')
            ->whereNull('event_hash')
            ->orderBy('id');

        if ($lastHashed) {
            $query->where('id', '>', $lastHashed->id);
        }

        $events = $query->get();

        foreach ($events as $event) {
            $eventProperties = json_decode((string) $event->event_properties, true);
            $hash = $this->computeHash(
                $event->id,
                $event->event_class,
                $eventProperties,
                $event->created_at,
                $previousHash
            );

            DB::table('stored_events')
                ->where('id', $event->id)
                ->update([
                    'event_hash' => $hash,
                    'previous_hash' => $previousHash,
                ]);

            $previousHash = $hash;
            $count++;
        }

        return $count;
    }
}
