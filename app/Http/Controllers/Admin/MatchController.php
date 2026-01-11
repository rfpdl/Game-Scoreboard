<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MatchController extends Controller
{
    /**
     * List all matches.
     */
    public function index(Request $request): Response
    {
        $query = GameMatch::with(['game', 'players.user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by game
        if ($request->has('game') && $request->game) {
            $query->where('game_id', $request->game);
        }

        // Filter by user
        if ($request->has('user') && $request->user) {
            $query->whereHas('players', function ($q) use ($request): void {
                $q->where('user_id', $request->user);
            });
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $matches = $query->paginate(20)->through(fn (GameMatch $match): array => [
            'uuid' => $match->uuid,
            'code' => $match->match_code,
            'status' => $match->status,
            'format' => $match->match_format,
            'game' => $match->game ? [
                'name' => $match->game->name,
                'icon' => $match->game->icon,
                'slug' => $match->game->slug,
            ] : null,
            'players' => $match->players->map(fn ($mp): array => [
                'id' => $mp->user_id,
                'name' => $mp->user->display_name,
                'team' => $mp->team,
                'isConfirmed' => $mp->confirmed_at !== null,
                'isWinner' => $mp->result === 'win',
                'ratingBefore' => $mp->rating_before,
                'ratingAfter' => $mp->rating_after,
                'ratingChange' => $mp->rating_change,
            ]),
            'createdAt' => $match->created_at->toDateTimeString(),
            'completedAt' => $match->played_at?->toDateTimeString(),
        ]);

        $games = Game::orderBy('name')->get(['id', 'name', 'icon']);
        $users = User::orderBy('name')->get(['id', 'name', 'nickname'])->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'displayName' => $user->display_name,
        ]);

        return Inertia::render('Admin/Matches/Index', [
            'matches' => $matches,
            'games' => $games,
            'users' => $users,
            'filters' => [
                'status' => $request->status ?? 'all',
                'game' => $request->game ?? null,
                'user' => $request->user ? (int) $request->user : null,
                'date_from' => $request->date_from ?? null,
                'date_to' => $request->date_to ?? null,
            ],
        ]);
    }
}
