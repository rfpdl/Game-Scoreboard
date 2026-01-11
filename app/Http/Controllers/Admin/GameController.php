<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\GameData;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GameController extends Controller
{
    /**
     * List all games.
     */
    public function index(): Response
    {
        $games = Game::orderBy('name')->get()->map(fn (Game $game): GameData => GameData::fromModel($game));

        return Inertia::render('Admin/Games/Index', [
            'games' => $games,
        ]);
    }

    /**
     * Show create game form.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Games/Create');
    }

    /**
     * Store a new game.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:games',
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'min_players' => 'integer|min:2|max:10',
            'max_players' => 'integer|min:2|max:10',
        ]);

        // Convert rules from newline-separated string to array
        $rules = null;
        if (! empty($validated['rules'])) {
            $rules = array_filter(array_map(trim(...), explode("\n", (string) $validated['rules'])));
            $rules = array_values($rules); // Re-index array
        }

        $game = Game::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'rules' => $rules,
            'icon' => $validated['icon'] ?? null,
            'min_players' => $validated['min_players'] ?? 2,
            'max_players' => $validated['max_players'] ?? 2,
            'is_active' => true,
        ]);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'created',
            subjectType: 'game',
            subjectId: $game->id,
            subjectName: $game->name,
            ipAddress: $request->ip(),
        );

        return redirect()->route('admin.games.index')->with('success', 'Game created.');
    }

    /**
     * Show edit game form.
     */
    public function edit(Game $game): Response
    {
        return Inertia::render('Admin/Games/Edit', [
            'game' => GameData::fromModel($game),
        ]);
    }

    /**
     * Update a game.
     */
    public function update(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:games,slug,'.$game->id,
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'min_players' => 'integer|min:2|max:10',
            'max_players' => 'integer|min:2|max:10',
        ]);

        // Convert rules from newline-separated string to array
        $rules = null;
        if (! empty($validated['rules'])) {
            $rules = array_filter(array_map(trim(...), explode("\n", (string) $validated['rules'])));
            $rules = array_values($rules); // Re-index array
        }

        $game->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'rules' => $rules,
            'icon' => $validated['icon'] ?? null,
            'min_players' => $validated['min_players'] ?? 2,
            'max_players' => $validated['max_players'] ?? 2,
        ]);

        // Cache invalidation is handled by GameObserver

        return redirect()->route('admin.games.index')->with('success', 'Game updated.');
    }

    /**
     * Toggle game active status.
     */
    public function toggle(Game $game): RedirectResponse
    {
        $game->update(['is_active' => ! $game->is_active]);

        // Cache invalidation is handled by GameObserver

        return redirect()->back();
    }

    /**
     * Delete a game.
     */
    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        // Cache invalidation is handled by GameObserver

        return redirect()->route('admin.games.index')->with('success', 'Game deleted.');
    }
}
