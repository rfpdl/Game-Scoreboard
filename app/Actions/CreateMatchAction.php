<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Match\Aggregates\MatchAggregate;
use App\Domain\Rating\Services\EloCalculator;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\PlayerRating;
use App\Models\User;
use Illuminate\Support\Str;

final class CreateMatchAction
{
    public function execute(
        User $user,
        Game $game,
        string $matchType = 'quick',
        ?string $name = null,
        ?string $scheduledAt = null,
        string $matchFormat = '1v1',
        ?int $maxPlayers = null,
    ): GameMatch {
        $matchUuid = (string) Str::uuid();

        // Determine max players based on format if not provided
        if ($maxPlayers === null) {
            $maxPlayers = match ($matchFormat) {
                '1v1' => 2,
                '2v2' => 4,
                '3v3' => 6,
                '4v4' => 8,
                'ffa' => 8, // Default FFA to 8 players
                default => 2,
            };
        }

        // Ensure player has a rating for this game
        $rating = $this->ensurePlayerRating($user, $game);

        // Determine team for creator (first player on team_a for team matches)
        $isTeamMatch = in_array($matchFormat, ['2v2', '3v3', '4v4']);
        $team = $isTeamMatch ? 'team_a' : null;

        // Create match via aggregate
        MatchAggregate::retrieve($matchUuid)
            ->createMatch(
                gameId: $game->id,
                matchCode: GameMatch::generateMatchCode(),
                createdByUserId: $user->id,
                matchType: $matchType,
                name: $name,
                scheduledAt: $scheduledAt,
                shareToken: GameMatch::generateShareToken(),
                matchFormat: $matchFormat,
                maxPlayers: $maxPlayers,
            )
            ->addPlayer(
                userId: $user->id,
                ratingBefore: $rating->rating,
                team: $team,
            )
            ->persist();

        return GameMatch::where('uuid', $matchUuid)->firstOrFail();
    }

    private function ensurePlayerRating(User $user, Game $game): PlayerRating
    {
        return PlayerRating::firstOrCreate(
            [
                'user_id' => $user->id,
                'game_id' => $game->id,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'rating' => EloCalculator::defaultRating(),
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'win_streak' => 0,
                'best_rating' => EloCalculator::defaultRating(),
            ]
        );
    }
}
