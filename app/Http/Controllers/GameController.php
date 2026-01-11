<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GameData;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GameController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Admin sees all games, regular users see only active games
        $query = $user && $user->is_admin ? Game::query() : Game::active();

        $games = $query->orderBy('name')->get()->map(fn (Game $game): GameData => GameData::fromModel($game));

        return Inertia::render('Games/Index', [
            'games' => $games,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:games',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'min_players' => 'integer|min:2|max:10',
            'max_players' => 'integer|min:2|max:10',
        ]);

        Game::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'min_players' => $validated['min_players'] ?? 2,
            'max_players' => $validated['max_players'] ?? 2,
            'is_active' => true,
        ]);

        return redirect()->route('games.index');
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:games,slug,'.$game->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'min_players' => 'integer|min:2|max:10',
            'max_players' => 'integer|min:2|max:10',
        ]);

        $game->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'min_players' => $validated['min_players'] ?? 2,
            'max_players' => $validated['max_players'] ?? 2,
        ]);

        return redirect()->route('games.index');
    }

    public function toggle(Game $game): RedirectResponse
    {
        $game->update([
            'is_active' => ! $game->is_active,
        ]);

        return redirect()->route('games.index');
    }

    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        return redirect()->route('games.index');
    }
}
