<?php

declare(strict_types=1);

namespace App\Domain\Match\Aggregates;

use App\Domain\Match\Events\MatchCancelled;
use App\Domain\Match\Events\MatchCompleted;
use App\Domain\Match\Events\MatchConfirmed;
use App\Domain\Match\Events\MatchCreated;
use App\Domain\Match\Events\MatchFormatChanged;
use App\Domain\Match\Events\MatchResultsRecorded;
use App\Domain\Match\Events\PlayerJoinedMatch;
use App\Domain\Match\Events\PlayerLeftMatch;
use App\Domain\Match\Events\PlayerSwitchedTeam;
use App\Domain\Rating\Services\EloCalculator;
use App\Models\Setting;
use DomainException;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

final class MatchAggregate extends AggregateRoot
{
    private string $status = 'pending';

    private int $createdByUserId;

    private string $matchFormat = '1v1';

    private int $maxPlayers = 2;

    private array $players = [];

    public function createMatch(
        int $gameId,
        string $matchCode,
        int $createdByUserId,
        string $matchType = 'quick',
        ?string $name = null,
        ?string $scheduledAt = null,
        ?string $shareToken = null,
        string $matchFormat = '1v1',
        int $maxPlayers = 2,
    ): self {
        $this->recordThat(new MatchCreated(
            matchUuid: $this->uuid(),
            gameId: $gameId,
            matchCode: $matchCode,
            createdByUserId: $createdByUserId,
            matchType: $matchType,
            name: $name,
            scheduledAt: $scheduledAt,
            shareToken: $shareToken,
            matchFormat: $matchFormat,
            maxPlayers: $maxPlayers,
        ));

        return $this;
    }

    public function addPlayer(int $userId, int $ratingBefore, ?string $team = null): self
    {
        if ($this->status !== 'pending') {
            throw new DomainException('Cannot add player to non-pending match');
        }

        if (count($this->players) >= $this->maxPlayers) {
            throw new DomainException('Match already has maximum players');
        }

        if (in_array($userId, array_column($this->players, 'user_id'))) {
            throw new DomainException('Player already in match');
        }

        // For team matches, auto-assign team if not provided
        $isTeamMatch = in_array($this->matchFormat, ['2v2', '3v3', '4v4']);
        if ($isTeamMatch && $team === null) {
            $playersPerTeam = match ($this->matchFormat) {
                '2v2' => 2,
                '3v3' => 3,
                '4v4' => 4,
                default => 2,
            };
            $teamACounts = count(array_filter($this->players, fn (array $p): bool => $p['team'] === 'team_a'));
            $team = $teamACounts < $playersPerTeam ? 'team_a' : 'team_b';
        }

        $this->recordThat(new PlayerJoinedMatch(
            matchUuid: $this->uuid(),
            userId: $userId,
            ratingBefore: $ratingBefore,
            team: $team,
        ));

        return $this;
    }

    public function confirmMatch(): self
    {
        if ($this->status !== 'pending') {
            throw new DomainException('Match is not pending');
        }

        $minPlayers = $this->matchFormat === 'ffa' ? 3 : $this->maxPlayers;
        if (count($this->players) < $minPlayers) {
            throw new DomainException(sprintf('Match needs at least %d players to confirm', $minPlayers));
        }

        $this->recordThat(new MatchConfirmed(
            matchUuid: $this->uuid(),
        ));

        return $this;
    }

    public function completeMatch(
        int $winnerId,
        int $winnerMatchesPlayed = 0,
        int $loserMatchesPlayed = 0,
        int $loserWinStreak = 0,
    ): self {
        if ($this->status !== 'confirmed') {
            throw new DomainException('Match must be confirmed to complete');
        }

        $winner = collect($this->players)->firstWhere('user_id', $winnerId);
        $loser = collect($this->players)->firstWhere('user_id', '!=', $winnerId);

        if (! $winner || ! $loser) {
            throw new DomainException('Invalid winner ID');
        }

        $calculator = new EloCalculator();
        $streakMultiplierEnabled = filter_var(Setting::get('streak_multiplier_enabled', false), FILTER_VALIDATE_BOOLEAN);

        $changes = $calculator->calculateMatch(
            $winner['rating_before'],
            $loser['rating_before'],
            EloCalculator::kFactorForExperience($winnerMatchesPlayed),
            EloCalculator::kFactorForExperience($loserMatchesPlayed),
            $loserWinStreak,
            $streakMultiplierEnabled,
        );

        $this->recordThat(new MatchCompleted(
            matchUuid: $this->uuid(),
            winnerId: $winnerId,
            loserId: $loser['user_id'],
            winnerRatingBefore: $winner['rating_before'],
            loserRatingBefore: $loser['rating_before'],
            winnerRatingChange: $changes['winner_change'],
            loserRatingChange: $changes['loser_change'],
        ));

        return $this;
    }

    public function removePlayer(int $userId): self
    {
        if ($this->status !== 'pending') {
            throw new DomainException('Cannot leave a match that is not pending');
        }

        if (! in_array($userId, array_column($this->players, 'user_id'))) {
            throw new DomainException('Player is not in this match');
        }

        // Creator cannot leave - they must cancel instead
        if ($userId === $this->createdByUserId) {
            throw new DomainException('Match creator cannot leave. Cancel the match instead.');
        }

        $this->recordThat(new PlayerLeftMatch(
            matchUuid: $this->uuid(),
            userId: $userId,
        ));

        return $this;
    }

    public function switchTeam(int $userId): self
    {
        if ($this->status !== 'pending') {
            throw new DomainException('Cannot switch teams in a non-pending match');
        }

        $isTeamMatch = in_array($this->matchFormat, ['2v2', '3v3', '4v4']);
        if (! $isTeamMatch) {
            throw new DomainException('Cannot switch teams in a non-team match');
        }

        $player = collect($this->players)->firstWhere('user_id', $userId);
        if (! $player) {
            throw new DomainException('Player is not in this match');
        }

        // Get the playersPerTeam based on format
        $playersPerTeam = match ($this->matchFormat) {
            '2v2' => 2,
            '3v3' => 3,
            '4v4' => 4,
            default => 2,
        };

        $currentTeam = $player['team'];
        $newTeam = $currentTeam === 'team_a' ? 'team_b' : 'team_a';

        // Check if the target team has room
        $targetTeamCount = count(array_filter($this->players, fn (array $p): bool => $p['team'] === $newTeam));
        if ($targetTeamCount >= $playersPerTeam) {
            throw new DomainException('Target team is already full');
        }

        $this->recordThat(new PlayerSwitchedTeam(
            matchUuid: $this->uuid(),
            userId: $userId,
            newTeam: $newTeam,
        ));

        return $this;
    }

    public function changeFormat(string $newFormat, int $newMaxPlayers): self
    {
        if ($this->status !== 'pending') {
            throw new DomainException('Cannot change format of a non-pending match');
        }

        // Check if we have too many players for the new format
        if (count($this->players) > $newMaxPlayers) {
            throw new DomainException('Too many players for the new format. Remove players first.');
        }

        $this->recordThat(new MatchFormatChanged(
            matchUuid: $this->uuid(),
            newFormat: $newFormat,
            newMaxPlayers: $newMaxPlayers,
        ));

        return $this;
    }

    public function cancelMatch(?string $reason = null): self
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            throw new DomainException('Cannot cancel completed or already cancelled match');
        }

        $this->recordThat(new MatchCancelled(
            matchUuid: $this->uuid(),
            reason: $reason,
        ));

        return $this;
    }

    /**
     * Complete a team match (2v2, 3v3, 4v4) with the winning team.
     *
     * @param  string  $winningTeam  'team_a' or 'team_b'
     * @param  array<int, array{matches_played: int, win_streak: int}>  $playerStats
     */
    public function completeTeamMatch(string $winningTeam, array $playerStats = []): self
    {
        if ($this->status !== 'confirmed') {
            throw new DomainException('Match must be confirmed to complete');
        }

        $isTeamMatch = in_array($this->matchFormat, ['2v2', '3v3', '4v4']);
        if (! $isTeamMatch) {
            throw new DomainException('This method is only for team matches (2v2, 3v3, 4v4)');
        }

        $calculator = new EloCalculator();
        $streakMultiplierEnabled = filter_var(Setting::get('streak_multiplier_enabled', false), FILTER_VALIDATE_BOOLEAN);

        // Group players by team
        $teamA = array_filter($this->players, fn (array $p): bool => $p['team'] === 'team_a');
        $teamB = array_filter($this->players, fn (array $p): bool => $p['team'] === 'team_b');

        // Calculate team average ratings
        $teamARating = $teamA !== [] ? (int) round(array_sum(array_column($teamA, 'rating_before')) / count($teamA)) : 1000;
        $teamBRating = $teamB !== [] ? (int) round(array_sum(array_column($teamB, 'rating_before')) / count($teamB)) : 1000;

        $winners = $winningTeam === 'team_a' ? $teamA : $teamB;
        $losers = $winningTeam === 'team_a' ? $teamB : $teamA;
        $winnerRating = $winningTeam === 'team_a' ? $teamARating : $teamBRating;
        $loserRating = $winningTeam === 'team_a' ? $teamBRating : $teamARating;

        // Get max win streak from losing team for streak bonus
        $maxLoserWinStreak = 0;
        foreach ($losers as $loser) {
            $stats = $playerStats[$loser['user_id']] ?? ['matches_played' => 0, 'win_streak' => 0];
            $maxLoserWinStreak = max($maxLoserWinStreak, $stats['win_streak']);
        }

        // Calculate rating changes using team averages
        $changes = $calculator->calculateMatch(
            $winnerRating,
            $loserRating,
            32, // Default K-factor for team matches
            32,
            $maxLoserWinStreak,
            $streakMultiplierEnabled,
        );

        // Build player results
        $playerResults = [];
        foreach ($winners as $winner) {
            $playerResults[] = [
                'user_id' => $winner['user_id'],
                'result' => 'win',
                'placement' => null,
                'rating_before' => $winner['rating_before'],
                'rating_change' => $changes['winner_change'],
                'team' => $winner['team'],
            ];
        }

        foreach ($losers as $loser) {
            $playerResults[] = [
                'user_id' => $loser['user_id'],
                'result' => 'lose',
                'placement' => null,
                'rating_before' => $loser['rating_before'],
                'rating_change' => $changes['loser_change'],
                'team' => $loser['team'],
            ];
        }

        $this->recordThat(new MatchResultsRecorded(
            matchUuid: $this->uuid(),
            matchFormat: $this->matchFormat,
            playerResults: $playerResults,
            winningTeam: $winningTeam,
        ));

        return $this;
    }

    /**
     * Complete an FFA match with player placements.
     *
     * @param  array<int, int>  $placements  User ID => placement (1 = first, 2 = second, etc.)
     * @param  array<int, array{matches_played: int, win_streak: int}>  $playerStats
     */
    public function completeFfaMatch(array $placements, array $playerStats = []): self
    {
        if ($this->status !== 'confirmed') {
            throw new DomainException('Match must be confirmed to complete');
        }

        if ($this->matchFormat !== 'ffa') {
            throw new DomainException('This method is only for FFA matches');
        }

        new EloCalculator();
        $playerCount = count($this->players);

        // Build player results with ELO changes based on placement
        $playerResults = [];
        foreach ($this->players as $player) {
            $userId = $player['user_id'];
            $placement = $placements[$userId] ?? $playerCount; // Default to last place

            // Calculate ELO change based on placement
            // First place gets positive change, last place gets negative
            // Middle places get proportional changes
            $expectedPlacement = ($playerCount + 1) / 2; // Average expected placement
            $placementDiff = $expectedPlacement - $placement; // Positive if better than expected
            $kFactor = EloCalculator::kFactorForExperience($playerStats[$userId]['matches_played'] ?? 0);
            $ratingChange = (int) round($placementDiff * $kFactor / 2);

            $playerResults[] = [
                'user_id' => $userId,
                'result' => $placement === 1 ? 'win' : ($placement === $playerCount ? 'lose' : 'draw'),
                'placement' => $placement,
                'rating_before' => $player['rating_before'],
                'rating_change' => $ratingChange,
                'team' => null,
            ];
        }

        $this->recordThat(new MatchResultsRecorded(
            matchUuid: $this->uuid(),
            matchFormat: $this->matchFormat,
            playerResults: $playerResults,
        ));

        return $this;
    }

    public function getMatchFormat(): string
    {
        return $this->matchFormat;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    protected function applyMatchCreated(MatchCreated $event): void
    {
        $this->createdByUserId = $event->createdByUserId;
        $this->matchFormat = $event->matchFormat;
        $this->maxPlayers = $event->maxPlayers;
        $this->status = 'pending';
    }

    protected function applyPlayerJoinedMatch(PlayerJoinedMatch $event): void
    {
        $this->players[] = [
            'user_id' => $event->userId,
            'rating_before' => $event->ratingBefore,
            'team' => $event->team,
        ];
    }

    protected function applyMatchConfirmed(MatchConfirmed $event): void
    {
        $this->status = 'confirmed';
    }

    protected function applyMatchCompleted(MatchCompleted $event): void
    {
        $this->status = 'completed';
    }

    protected function applyMatchCancelled(MatchCancelled $event): void
    {
        $this->status = 'cancelled';
    }

    protected function applyPlayerLeftMatch(PlayerLeftMatch $event): void
    {
        $this->players = array_filter(
            $this->players,
            fn (array $player): bool => $player['user_id'] !== $event->userId
        );
    }

    protected function applyPlayerSwitchedTeam(PlayerSwitchedTeam $event): void
    {
        $this->players = array_map(function (array $player) use ($event): array {
            if ($player['user_id'] === $event->userId) {
                $player['team'] = $event->newTeam;
            }

            return $player;
        }, $this->players);
    }

    protected function applyMatchFormatChanged(MatchFormatChanged $event): void
    {
        $oldFormat = $this->matchFormat;
        $this->matchFormat = $event->newFormat;
        $this->maxPlayers = $event->newMaxPlayers;

        // Update team assignments based on new format
        $isNewTeamMatch = in_array($event->newFormat, ['2v2', '3v3', '4v4']);
        $wasTeamMatch = in_array($oldFormat, ['2v2', '3v3', '4v4']);

        if ($isNewTeamMatch && ! $wasTeamMatch) {
            // Switching to team format - alternate teams for even distribution
            $index = 0;
            $this->players = array_map(function (array $player) use (&$index): array {
                $player['team'] = ($index % 2 === 0) ? 'team_a' : 'team_b';
                $index++;

                return $player;
            }, $this->players);
        } elseif (! $isNewTeamMatch) {
            // Switching to non-team format - clear teams
            $this->players = array_map(function (array $player): array {
                $player['team'] = null;

                return $player;
            }, $this->players);
        }
    }

    protected function applyMatchResultsRecorded(MatchResultsRecorded $event): void
    {
        $this->status = 'completed';
    }
}
