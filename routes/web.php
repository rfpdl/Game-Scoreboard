<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Install wizard (excluded from EnsureSetupComplete middleware)
Route::withoutMiddleware(App\Http\Middleware\EnsureSetupComplete::class)->group(function () {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install/settings', [InstallController::class, 'storeSettings'])->name('install.settings');
    Route::post('/install/logo', [InstallController::class, 'storeLogo'])->name('install.logo');
    Route::delete('/install/logo', [InstallController::class, 'removeLogo'])->name('install.logo.remove');
    Route::post('/install/admin', [InstallController::class, 'storeAdmin'])->name('install.admin');
});

// Debug route for cookie troubleshooting (TEMPORARY)
Route::get('/debug-cookies', function () {
    return response()->json([
        'cookies_received' => array_keys($_COOKIE),
        'has_xsrf' => isset($_COOKIE['XSRF-TOKEN']),
        'has_session' => isset($_COOKIE[config('session.cookie')]),
        'session_config' => [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
        ],
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
    ]);
});

// Public routes
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/leaderboard/{game?}', [LeaderboardController::class, 'index'])
    ->name('leaderboard.index');
Route::get('/leaderboard/{game}/{user}/matches', [LeaderboardController::class, 'playerMatches'])
    ->name('leaderboard.player.matches');

// Public profile by nickname
Route::get('/@{nickname}', [PublicProfileController::class, 'show'])
    ->name('profile.public')
    ->where('nickname', '[a-z0-9][a-z0-9-]*[a-z0-9]|[a-z0-9]');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Games
    Route::get('/games', [GameController::class, 'index'])
        ->name('games.index');
    Route::post('/games', [GameController::class, 'store'])
        ->name('games.store')
        ->middleware('can:admin');
    Route::put('/games/{game}', [GameController::class, 'update'])
        ->name('games.update')
        ->middleware('can:admin');
    Route::patch('/games/{game}/toggle', [GameController::class, 'toggle'])
        ->name('games.toggle')
        ->middleware('can:admin');
    Route::delete('/games/{game}', [GameController::class, 'destroy'])
        ->name('games.destroy')
        ->middleware('can:admin');

    // Matches
    Route::get('/matches/create', [MatchController::class, 'create'])
        ->name('matches.create');
    Route::post('/matches', [MatchController::class, 'store'])
        ->name('matches.store');
    Route::get('/matches/join', [MatchController::class, 'join'])
        ->name('matches.join');
    Route::get('/matches/{token}/join', [MatchController::class, 'joinByShareToken'])
        ->name('matches.joinByShareToken');
    Route::post('/matches/join', [MatchController::class, 'joinByCode'])
        ->name('matches.joinByCode');
    Route::get('/matches/{uuid}', [MatchController::class, 'show'])
        ->name('matches.show');
    Route::post('/matches/{uuid}/confirm', [MatchController::class, 'confirm'])
        ->name('matches.confirm');
    Route::post('/matches/{uuid}/complete', [MatchController::class, 'complete'])
        ->name('matches.complete');
    Route::post('/matches/{uuid}/cancel', [MatchController::class, 'cancel'])
        ->name('matches.cancel');
    Route::post('/matches/{uuid}/leave', [MatchController::class, 'leave'])
        ->name('matches.leave');
    Route::post('/matches/{uuid}/switch-team', [MatchController::class, 'switchTeam'])
        ->name('matches.switchTeam');
    Route::post('/matches/{uuid}/change-format', [MatchController::class, 'changeFormat'])
        ->name('matches.changeFormat');
    Route::get('/matches/search-users', [MatchController::class, 'searchUsers'])
        ->name('matches.searchUsers');
    Route::post('/matches/{uuid}/invite', [MatchController::class, 'invite'])
        ->name('matches.invite');

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])
        ->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])
        ->name('profile.avatar.remove');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
    Route::delete('/settings/logo', [SettingsController::class, 'removeLogo'])->name('settings.logo.remove');

    // Games
    Route::get('/games', [AdminGameController::class, 'index'])->name('games.index');
    Route::get('/games/create', [AdminGameController::class, 'create'])->name('games.create');
    Route::post('/games', [AdminGameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}/edit', [AdminGameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [AdminGameController::class, 'update'])->name('games.update');
    Route::patch('/games/{game}/toggle', [AdminGameController::class, 'toggle'])->name('games.toggle');
    Route::delete('/games/{game}', [AdminGameController::class, 'destroy'])->name('games.destroy');

    // Matches
    Route::get('/matches', [App\Http\Controllers\Admin\MatchController::class, 'index'])->name('matches.index');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::patch('/users/{user}/toggle-disabled', [AdminUserController::class, 'toggleDisabled'])->name('users.toggle-disabled');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Backups
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');
    Route::put('/backups/settings', [BackupController::class, 'updateSettings'])->name('backups.settings');
});

require __DIR__.'/auth.php';
