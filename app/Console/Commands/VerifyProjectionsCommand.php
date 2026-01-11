<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GameMatch;
use App\Models\HashedStoredEvent;
use App\Models\PlayerRating;
use App\Services\EventHasher;
use Illuminate\Console\Command;

final class VerifyProjectionsCommand extends Command
{
    protected $signature = 'projections:verify
                            {--fix : Attempt to fix discrepancies by replaying events}
                            {--events-only : Only verify event chain integrity}';

    protected $description = 'Verify integrity of event chain and projections';

    public function handle(EventHasher $hasher): int
    {
        $this->info('Starting integrity verification...');
        $this->newLine();

        // Step 1: Verify event chain
        $this->info('Step 1: Verifying event chain integrity...');
        $brokenLinks = $hasher->verifyChain();

        if (count($brokenLinks) > 0) {
            $this->error('  Event chain is BROKEN! Found '.count($brokenLinks).' broken links.');
            foreach ($brokenLinks as $link) {
                $this->error("    Event #{$link['event_id']}: {$link['error']}");
            }

            return self::FAILURE;
        }

        $eventCount = HashedStoredEvent::count();
        $this->info("  Event chain OK ({$eventCount} events verified)");

        if ($this->option('events-only')) {
            $this->newLine();
            $this->info('Event chain verification complete.');

            return self::SUCCESS;
        }

        $this->newLine();

        // Step 2: Verify match projections
        $this->info('Step 2: Verifying match projections...');
        $matchDiscrepancies = $this->verifyMatchProjections();

        if ($matchDiscrepancies['count'] > 0) {
            $this->warn("  Found {$matchDiscrepancies['count']} match discrepancies.");
            foreach ($matchDiscrepancies['details'] as $detail) {
                $this->warn("    {$detail}");
            }
        } else {
            $this->info('  Match projections OK');
        }

        $this->newLine();

        // Step 3: Verify rating projections
        $this->info('Step 3: Verifying rating projections...');
        $ratingDiscrepancies = $this->verifyRatingProjections();

        if ($ratingDiscrepancies['count'] > 0) {
            $this->warn("  Found {$ratingDiscrepancies['count']} rating discrepancies.");
            foreach (array_slice($ratingDiscrepancies['details'], 0, 10) as $detail) {
                $this->warn("    {$detail}");
            }
            if (count($ratingDiscrepancies['details']) > 10) {
                $this->warn('    ... and '.(count($ratingDiscrepancies['details']) - 10).' more');
            }

            if ($this->option('fix')) {
                $this->newLine();
                $this->info('Attempting to fix discrepancies by replaying events...');
                $this->call('event-sourcing:replay', [
                    'projector' => 'App\\Domain\\Rating\\Projectors\\PlayerRatingProjector',
                ]);
                $this->info('Events replayed. Please run verify again to confirm.');
            }
        } else {
            $this->info('  Rating projections OK');
        }

        $this->newLine();

        // Summary
        $this->info('Verification Summary:');
        $this->table(
            ['Check', 'Status'],
            [
                ['Event Chain', 'PASS'],
                ['Match Projections', $matchDiscrepancies['count'] > 0 ? 'WARN' : 'PASS'],
                ['Rating Projections', $ratingDiscrepancies['count'] > 0 ? 'WARN' : 'PASS'],
            ]
        );

        return $matchDiscrepancies['count'] > 0 || $ratingDiscrepancies['count'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Verify match projections against stored events.
     */
    private function verifyMatchProjections(): array
    {
        $discrepancies = [];
        $count = 0;

        // Get all completed matches from events
        $completedEvents = HashedStoredEvent::where('event_class', 'like', '%MatchCompleted%')
            ->orWhere('event_class', 'like', '%MatchResultsRecorded%')
            ->get();

        $completedUuids = $completedEvents->map(function ($event) {
            $props = is_string($event->event_properties)
                ? json_decode($event->event_properties, true)
                : $event->event_properties;

            return $props['matchUuid'] ?? null;
        })->filter()->unique();

        // Check that all completed events have completed matches in projection
        foreach ($completedUuids as $uuid) {
            $match = GameMatch::where('uuid', $uuid)->first();
            if (! $match) {
                $discrepancies[] = "Match {$uuid}: exists in events but not in projection";
                $count++;
            } elseif ($match->status !== 'completed') {
                $discrepancies[] = "Match {$uuid}: status should be 'completed' but is '{$match->status}'";
                $count++;
            }
        }

        // Check for orphan completed matches (in projection but not in events)
        $projectedCompleted = GameMatch::where('status', 'completed')
            ->whereNotIn('uuid', $completedUuids)
            ->get();

        foreach ($projectedCompleted as $match) {
            $discrepancies[] = "Match {$match->uuid}: marked completed in projection but no completion event found";
            $count++;
        }

        return ['count' => $count, 'details' => $discrepancies];
    }

    /**
     * Verify rating projections against stored events.
     */
    private function verifyRatingProjections(): array
    {
        $discrepancies = [];
        $count = 0;

        // Calculate expected ratings from events
        $expectedRatings = $this->calculateExpectedRatings();

        // Compare with actual projections
        foreach ($expectedRatings as $key => $expected) {
            [$userId, $gameId] = explode('-', $key);

            $actual = PlayerRating::where('user_id', $userId)
                ->where('game_id', $gameId)
                ->first();

            if (! $actual) {
                $discrepancies[] = "User {$userId}, Game {$gameId}: rating record missing";
                $count++;

                continue;
            }

            if ($actual->rating !== $expected['rating']) {
                $discrepancies[] = "User {$userId}, Game {$gameId}: rating should be {$expected['rating']} but is {$actual->rating}";
                $count++;
            }

            if ($actual->matches_played !== $expected['matches_played']) {
                $discrepancies[] = "User {$userId}, Game {$gameId}: matches_played should be {$expected['matches_played']} but is {$actual->matches_played}";
                $count++;
            }

            if ($actual->wins !== $expected['wins']) {
                $discrepancies[] = "User {$userId}, Game {$gameId}: wins should be {$expected['wins']} but is {$actual->wins}";
                $count++;
            }

            if ($actual->losses !== $expected['losses']) {
                $discrepancies[] = "User {$userId}, Game {$gameId}: losses should be {$expected['losses']} but is {$actual->losses}";
                $count++;
            }
        }

        return ['count' => $count, 'details' => $discrepancies];
    }

    /**
     * Calculate expected ratings by replaying events.
     */
    private function calculateExpectedRatings(): array
    {
        $ratings = [];

        // Get initial ratings from PlayerRegisteredForGame events
        $registrationEvents = HashedStoredEvent::where('event_class', 'like', '%PlayerRegisteredForGame%')
            ->orderBy('id')
            ->get();

        foreach ($registrationEvents as $event) {
            $props = $this->getEventProperties($event);
            $key = $props['userId'].'-'.$props['gameId'];
            $ratings[$key] = [
                'rating' => $props['initialRating'],
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
            ];
        }

        // Apply MatchCompleted events
        $completedEvents = HashedStoredEvent::where('event_class', 'like', '%MatchCompleted%')
            ->orderBy('id')
            ->get();

        foreach ($completedEvents as $event) {
            $props = $this->getEventProperties($event);
            $match = GameMatch::where('uuid', $props['matchUuid'])->first();
            if (! $match) {
                continue;
            }

            $winnerKey = $props['winnerId'].'-'.$match->game_id;
            $loserKey = $props['loserId'].'-'.$match->game_id;

            if (isset($ratings[$winnerKey])) {
                $ratings[$winnerKey]['rating'] = $props['winnerRatingBefore'] + $props['winnerRatingChange'];
                $ratings[$winnerKey]['matches_played']++;
                $ratings[$winnerKey]['wins']++;
            }

            if (isset($ratings[$loserKey])) {
                $ratings[$loserKey]['rating'] = $props['loserRatingBefore'] + $props['loserRatingChange'];
                $ratings[$loserKey]['matches_played']++;
                $ratings[$loserKey]['losses']++;
            }
        }

        // Apply MatchResultsRecorded events (team/FFA)
        $resultsEvents = HashedStoredEvent::where('event_class', 'like', '%MatchResultsRecorded%')
            ->orderBy('id')
            ->get();

        foreach ($resultsEvents as $event) {
            $props = $this->getEventProperties($event);
            $match = GameMatch::where('uuid', $props['matchUuid'])->first();
            if (! $match) {
                continue;
            }

            foreach ($props['playerResults'] ?? [] as $result) {
                $key = $result['user_id'].'-'.$match->game_id;
                if (isset($ratings[$key])) {
                    $ratings[$key]['rating'] = $result['rating_before'] + $result['rating_change'];
                    $ratings[$key]['matches_played']++;
                    if ($result['result'] === 'win') {
                        $ratings[$key]['wins']++;
                    } else {
                        $ratings[$key]['losses']++;
                    }
                }
            }
        }

        return $ratings;
    }

    /**
     * Get event properties as array (handles both string and already-decoded).
     */
    private function getEventProperties(HashedStoredEvent $event): array
    {
        return is_string($event->event_properties)
            ? json_decode($event->event_properties, true)
            : $event->event_properties;
    }
}
