<?php

declare(strict_types=1);

namespace App\Domain\Match\Projectors;

use App\Domain\Match\Events\MatchCancelled;
use App\Domain\Match\Events\MatchCompleted;
use App\Domain\Match\Events\MatchConfirmed;
use App\Domain\Match\Events\MatchCreated;
use App\Domain\Match\Events\MatchFormatChanged;
use App\Domain\Match\Events\MatchResultsRecorded;
use App\Domain\Match\Events\PlayerJoinedMatch;
use App\Domain\Match\Events\PlayerLeftMatch;
use App\Domain\Match\Events\PlayerSwitchedTeam;
use App\Events\MatchUpdated;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class MatchProjector extends Projector
{
    public function onMatchCreated(MatchCreated $event): void
    {
        GameMatch::create([
            'uuid' => $event->matchUuid,
            'game_id' => $event->gameId,
            'match_code' => $event->matchCode,
            'match_format' => $event->matchFormat,
            'max_players' => $event->maxPlayers,
            'match_type' => $event->matchType,
            'name' => $event->name,
            'scheduled_at' => $event->scheduledAt,
            'share_token' => $event->shareToken,
            'created_by_user_id' => $event->createdByUserId,
            'status' => 'pending',
        ]);
    }

    public function onPlayerJoinedMatch(PlayerJoinedMatch $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        MatchPlayer::create([
            'match_id' => $match->id,
            'user_id' => $event->userId,
            'team' => $event->team,
            'rating_before' => $event->ratingBefore,
            'result' => 'pending',
        ]);

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'player_joined'));
    }

    public function onMatchConfirmed(MatchConfirmed $event): void
    {
        GameMatch::where('uuid', $event->matchUuid)->update([
            'status' => 'confirmed',
        ]);

        $match = GameMatch::where('uuid', $event->matchUuid)->first();
        $match->players()->update([
            'confirmed_at' => now(),
        ]);

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'confirmed'));
    }

    public function onMatchCompleted(MatchCompleted $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        $match->update([
            'status' => 'completed',
            'played_at' => now(),
        ]);

        // Update winner
        MatchPlayer::where('match_id', $match->id)
            ->where('user_id', $event->winnerId)
            ->update([
                'result' => 'win',
                'rating_after' => $event->winnerRatingBefore + $event->winnerRatingChange,
                'rating_change' => $event->winnerRatingChange,
            ]);

        // Update loser
        MatchPlayer::where('match_id', $match->id)
            ->where('user_id', $event->loserId)
            ->update([
                'result' => 'lose',
                'rating_after' => $event->loserRatingBefore + $event->loserRatingChange,
                'rating_change' => $event->loserRatingChange,
            ]);

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'completed'));
    }

    public function onMatchCancelled(MatchCancelled $event): void
    {
        GameMatch::where('uuid', $event->matchUuid)->update([
            'status' => 'cancelled',
        ]);

        $match = GameMatch::where('uuid', $event->matchUuid)->first();
        if ($match) {
            broadcast(new MatchUpdated($match, 'cancelled'));
        }
    }

    public function onPlayerLeftMatch(PlayerLeftMatch $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        MatchPlayer::where('match_id', $match->id)
            ->where('user_id', $event->userId)
            ->delete();

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'player_left'));
    }

    public function onPlayerSwitchedTeam(PlayerSwitchedTeam $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        MatchPlayer::where('match_id', $match->id)
            ->where('user_id', $event->userId)
            ->update(['team' => $event->newTeam]);

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'team_switched'));
    }

    public function onMatchFormatChanged(MatchFormatChanged $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();
        $oldFormat = $match->match_format;

        $match->update([
            'match_format' => $event->newFormat,
            'max_players' => $event->newMaxPlayers,
        ]);

        // Update team assignments based on new format
        $isNewTeamMatch = in_array($event->newFormat, ['2v2', '3v3', '4v4']);
        $wasTeamMatch = in_array($oldFormat, ['2v2', '3v3', '4v4']);

        if ($isNewTeamMatch && ! $wasTeamMatch) {
            // Switching to team format - alternate teams for even distribution
            $index = 0;
            foreach ($match->players as $player) {
                $team = ($index % 2 === 0) ? 'team_a' : 'team_b';
                $player->update(['team' => $team]);
                $index++;
            }
        } elseif (! $isNewTeamMatch) {
            // Switching to non-team format - clear teams
            MatchPlayer::where('match_id', $match->id)->update(['team' => null]);
        }

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'format_changed'));
    }

    public function onMatchResultsRecorded(MatchResultsRecorded $event): void
    {
        $match = GameMatch::where('uuid', $event->matchUuid)->firstOrFail();

        $match->update([
            'status' => 'completed',
            'played_at' => now(),
        ]);

        // Update each player's results
        foreach ($event->playerResults as $result) {
            MatchPlayer::where('match_id', $match->id)
                ->where('user_id', $result['user_id'])
                ->update([
                    'result' => $result['result'],
                    'placement' => $result['placement'] ?? null,
                    'rating_after' => $result['rating_before'] + $result['rating_change'],
                    'rating_change' => $result['rating_change'],
                ]);
        }

        // Broadcast match update
        broadcast(new MatchUpdated($match->fresh(), 'completed'));
    }
}
