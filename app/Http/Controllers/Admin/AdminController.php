<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => User::count(),
                'totalGames' => Game::count(),
                'totalMatches' => GameMatch::count(),
                'activeGames' => Game::where('is_active', true)->count(),
            ],
        ]);
    }
}
