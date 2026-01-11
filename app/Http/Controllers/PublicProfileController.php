<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MatchPlayer;
use App\Models\PlayerRating;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class PublicProfileController extends Controller
{
    public function show(string $nickname): Response
    {
        $user = User::where('nickname', $nickname)->firstOrFail();

        // Get user stats across all games
        $matchesPlayed = MatchPlayer::where('user_id', $user->id)
            ->whereHas('match', fn ($q) => $q->where('status', 'completed'))
            ->count();

        $matchesWon = MatchPlayer::where('user_id', $user->id)
            ->where('result', 'win')
            ->count();

        $matchesLost = MatchPlayer::where('user_id', $user->id)
            ->where('result', 'lose')
            ->count();

        $winRate = $matchesPlayed > 0 ? round(($matchesWon / $matchesPlayed) * 100) : 0;

        // Get ratings for all games
        $ratings = PlayerRating::with('game')
            ->where('user_id', $user->id)
            ->where('matches_played', '>', 0)
            ->orderByDesc('rating')
            ->get()
            ->map(fn ($rating): array => [
                'gameId' => $rating->game_id,
                'gameName' => $rating->game->name,
                'gameIcon' => $rating->game->icon,
                'rating' => $rating->rating,
                'matchesPlayed' => $rating->matches_played,
                'wins' => $rating->wins,
                'losses' => $rating->losses,
                'winStreak' => $rating->win_streak,
                'winRate' => $rating->matches_played > 0
                    ? round(($rating->wins / $rating->matches_played) * 100)
                    : 0,
            ]);

        // Get recent matches
        $recentMatches = MatchPlayer::with(['match.game', 'match.players.user'])
            ->where('user_id', $user->id)
            ->whereHas('match', fn ($q) => $q->where('status', 'completed'))
            ->orderByDesc(
                MatchPlayer::select('played_at')
                    ->from('matches')
                    ->whereColumn('matches.id', 'match_players.match_id')
            )
            ->take(10)
            ->get()
            ->map(function ($mp) use ($user): array {
                $match = $mp->match;
                $opponent = $match->players->first(fn ($p): bool => $p->user_id !== $user->id);

                $opponentAvatar = $opponent?->user?->avatar;

                return [
                    'id' => $match->id,
                    'uuid' => $match->uuid,
                    'game' => [
                        'name' => $match->game->name,
                        'icon' => $match->game->icon,
                    ],
                    'opponent' => $opponent ? [
                        'name' => $opponent->user->display_name,
                        'avatar' => $opponentAvatar ? '/storage/'.$opponentAvatar : null,
                    ] : null,
                    'result' => $mp->result,
                    'ratingChange' => $mp->rating_change,
                    'playedAt' => $match->played_at?->diffForHumans(),
                ];
            });

        return Inertia::render('PublicProfile/Show', [
            'profile' => [
                'name' => $user->display_name,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar ? '/storage/'.$user->avatar : null,
            ],
            'stats' => [
                'matchesPlayed' => $matchesPlayed,
                'matchesWon' => $matchesWon,
                'matchesLost' => $matchesLost,
                'winRate' => $winRate,
            ],
            'ratings' => $ratings,
            'recentMatches' => $recentMatches,
        ]);
    }
}
