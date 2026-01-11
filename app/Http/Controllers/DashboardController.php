<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GameData;
use App\Data\MatchData;
use App\Data\PlayerRatingData;
use App\Models\Game;
use App\Models\GameMatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's ratings for all games
        $ratings = $user->ratings()
            ->with('game')
            ->get()
            ->map(fn (\App\Models\PlayerRating $rating): PlayerRatingData => PlayerRatingData::fromModel($rating));

        // Get recent matches (exclude cancelled)
        $recentMatches = GameMatch::query()
            ->where('status', '!=', 'cancelled')
            ->with(['game', 'players.user'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn (GameMatch $match): MatchData => MatchData::fromModel($match));

        // Get pending matches created by user
        $pendingMatches = GameMatch::where('created_by_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['game', 'players.user'])
            ->get()
            ->map(fn (GameMatch $match): MatchData => MatchData::fromModel($match));

        // Get active games
        $games = Game::active()->get()->map(fn (Game $game): GameData => GameData::fromModel($game));

        return Inertia::render('Dashboard', [
            'ratings' => $ratings,
            'recentMatches' => $recentMatches,
            'pendingMatches' => $pendingMatches,
            'games' => $games,
        ]);
    }
}
