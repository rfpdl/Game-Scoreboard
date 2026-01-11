<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GameData;
use App\Data\MatchData;
use App\Data\PlayerRatingData;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\PlayerRating;
use App\Models\User;
use App\Services\LeaderboardCacheService;
use Inertia\Inertia;
use Inertia\Response;

final class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardCacheService $cacheService
    ) {}

    public function index(?string $gameSlug = null): Response
    {
        $games = Game::active()->get();
        $game = $gameSlug
            ? $games->firstWhere('slug', $gameSlug)
            : $games->first();

        $leaderboard = $game ? $this->cacheService->getLeaderboard($game) : [];

        return Inertia::render('Leaderboard/Index', [
            'games' => $games->map(fn (Game $g): GameData => GameData::fromModel($g)),
            'selectedGame' => $game ? GameData::fromModel($game) : null,
            'entries' => $leaderboard,
        ]);
    }

    public function playerMatches(string $gameSlug, int $userId): Response
    {
        $game = Game::where('slug', $gameSlug)->firstOrFail();
        $user = User::findOrFail($userId);

        $rating = PlayerRating::where('user_id', $userId)
            ->where('game_id', $game->id)
            ->first();

        // Get all matches for this player in this game
        $matches = GameMatch::where('game_id', $game->id)
            ->whereHas('players', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'completed')
            ->with(['game', 'createdBy', 'players.user'])
            ->orderByDesc('played_at')
            ->paginate(20);

        return Inertia::render('Leaderboard/PlayerMatches', [
            'game' => GameData::fromModel($game),
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ],
            'rating' => $rating ? PlayerRatingData::fromModel($rating) : null,
            'matches' => $matches->through(fn (GameMatch $m): MatchData => MatchData::fromModel($m)),
        ]);
    }
}
