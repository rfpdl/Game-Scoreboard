<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\MatchPlayer;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Get user stats
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
                    'rating_change' => $mp->rating_change,
                    'played_at' => $match->played_at?->diffForHumans(),
                ];
            });

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'stats' => [
                'matchesPlayed' => $matchesPlayed,
                'matchesWon' => $matchesWon,
                'matchesLost' => $matchesLost,
                'winRate' => $winRate,
            ],
            'recentMatches' => $recentMatches,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Upload the user's avatar.
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
