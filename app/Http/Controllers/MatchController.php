<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CancelMatchAction;
use App\Actions\ChangeMatchFormatAction;
use App\Actions\CompleteMatchAction;
use App\Actions\ConfirmMatchAction;
use App\Actions\CreateMatchAction;
use App\Actions\JoinMatchAction;
use App\Actions\LeaveMatchAction;
use App\Actions\SwitchTeamAction;
use App\Data\GameData;
use App\Data\MatchData;
use App\Models\Game;
use App\Models\GameMatch;
use App\Models\User;
use App\Notifications\MatchInviteNotification;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MatchController extends Controller
{
    public function __construct(
        private readonly CreateMatchAction $createMatchAction,
        private readonly JoinMatchAction $joinMatchAction,
        private readonly ConfirmMatchAction $confirmMatchAction,
        private readonly CompleteMatchAction $completeMatchAction,
        private readonly CancelMatchAction $cancelMatchAction,
        private readonly LeaveMatchAction $leaveMatchAction,
        private readonly SwitchTeamAction $switchTeamAction,
        private readonly ChangeMatchFormatAction $changeMatchFormatAction,
    ) {}

    public function create(): Response
    {
        $games = Game::active()->get()->map(fn (Game $game): GameData => GameData::fromModel($game));

        return Inertia::render('Matches/Create', [
            'games' => $games,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'match_type' => 'nullable|in:quick,booked',
            'match_format' => 'nullable|in:1v1,2v2,3v3,4v4,ffa',
            'max_players' => 'nullable|integer|min:2|max:16',
            'name' => 'nullable|string|max:100',
            'scheduled_at' => 'nullable|required_if:match_type,booked|date|after:now',
        ]);

        $game = Game::findOrFail($request->game_id);

        // Check if user has a pending or confirmed match for this game
        $existingMatch = GameMatch::where('game_id', $game->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('players', fn ($q) => $q->where('user_id', $request->user()->id))
            ->first();

        if ($existingMatch) {
            return back()->withErrors([
                'game_id' => 'You already have an active match for this game. Complete or cancel it first.',
            ]);
        }

        $match = $this->createMatchAction->execute(
            user: $request->user(),
            game: $game,
            matchType: $request->input('match_type', 'quick'),
            name: $request->input('name'),
            scheduledAt: $request->input('scheduled_at'),
            matchFormat: $request->input('match_format', '1v1'),
            maxPlayers: $request->input('max_players'),
        );

        return redirect()->route('matches.show', $match->uuid);
    }

    public function show(string $uuid): Response
    {
        $match = GameMatch::where('uuid', $uuid)
            ->with(['game', 'createdBy', 'players.user'])
            ->firstOrFail();

        return Inertia::render('Matches/Show', [
            'match' => MatchData::fromModel($match),
        ]);
    }

    public function join(): Response
    {
        return Inertia::render('Matches/Join');
    }

    public function joinByShareToken(Request $request, string $token): RedirectResponse
    {
        $match = GameMatch::where('share_token', $token)->first();

        if (! $match) {
            return redirect()->route('matches.join')->withErrors(['token' => 'Invalid share link']);
        }

        if (! $request->user()) {
            // Store intended URL and redirect to login
            session(['url.intended' => route('matches.joinByShareToken', ['token' => $token])]);

            return redirect()->route('login');
        }

        try {
            $this->joinMatchAction->execute($request->user(), $match);
        } catch (DomainException $domainException) {
            return redirect()->route('matches.show', $match->uuid)->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }

    public function joinByCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $match = GameMatch::where('match_code', mb_strtoupper($request->code))->first();

        if (! $match) {
            return back()->withErrors(['code' => 'Match not found']);
        }

        try {
            $this->joinMatchAction->execute($request->user(), $match);
        } catch (DomainException $domainException) {
            return back()->withErrors(['code' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }

    public function confirm(Request $request, string $uuid): RedirectResponse
    {
        $match = GameMatch::where('uuid', $uuid)->firstOrFail();

        try {
            $this->confirmMatchAction->execute($match);
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }

    public function complete(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'winner_id' => 'required|exists:users,id',
        ]);

        $match = GameMatch::where('uuid', $uuid)->firstOrFail();

        try {
            $this->completeMatchAction->execute(
                $match,
                User::findOrFail($request->winner_id)
            );
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }

    public function cancel(Request $request, string $uuid): RedirectResponse
    {
        $match = GameMatch::where('uuid', $uuid)->firstOrFail();

        try {
            $this->cancelMatchAction->execute($match, $request->user());
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('dashboard')->with('success', 'Match cancelled');
    }

    public function leave(Request $request, string $uuid): RedirectResponse
    {
        $match = GameMatch::where('uuid', $uuid)->firstOrFail();

        try {
            $this->leaveMatchAction->execute($match, $request->user());
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('dashboard')->with('success', 'You have left the match');
    }

    public function switchTeam(Request $request, string $uuid): RedirectResponse
    {
        $match = GameMatch::where('uuid', $uuid)->firstOrFail();

        try {
            $this->switchTeamAction->execute($match, $request->user());
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }

    public function changeFormat(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'format' => 'required|in:1v1,2v2,3v3,4v4,ffa',
        ]);

        $match = GameMatch::where('uuid', $uuid)->firstOrFail();

        // Only creator can change format
        if ($match->created_by_user_id !== $request->user()->id) {
            return back()->withErrors(['match' => 'Only the match creator can change the format.']);
        }

        try {
            $this->changeMatchFormatAction->execute($match, $request->format);
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }

    /**
     * Search for users to invite, or return list of available players if no query.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:50',
        ]);

        $query = $request->input('q');
        $currentUserId = $request->user()->id;
        $excludeIds = $request->input('exclude', []);
        if (! is_array($excludeIds)) {
            $excludeIds = explode(',', (string) $excludeIds);
        }

        $excludeIds = array_filter(array_map(intval(...), $excludeIds));
        $excludeIds[] = $currentUserId;

        $usersQuery = User::whereNotIn('id', $excludeIds)
            ->where('is_disabled', false);

        if ($query && mb_strlen((string) $query) >= 2) {
            // Search mode
            $usersQuery->where(function ($q) use ($query): void {
                $q->where('name', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('nickname', 'like', sprintf('%%%s%%', $query))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $query));
            });
        }

        // Order by recent activity (users who have played recently first)
        $users = $usersQuery
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get(['id', 'name', 'nickname', 'avatar']);

        return response()->json([
            'users' => $users->map(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->display_name,
                'avatar' => $user->avatar ? '/storage/'.$user->avatar : null,
            ]),
        ]);
    }

    /**
     * Invite a user to a match.
     */
    public function invite(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $match = GameMatch::where('uuid', $uuid)->with('game')->firstOrFail();
        $invitedUser = User::findOrFail($request->user_id);

        // Check authorization - only match creator can invite
        if ($match->created_by_user_id !== $request->user()->id) {
            return back()->withErrors(['match' => 'Only the match creator can invite players.']);
        }

        try {
            $this->joinMatchAction->execute($invitedUser, $match);

            // Send email notification if mail is configured
            $invitedUser->notify(new MatchInviteNotification($match, $request->user()));
        } catch (DomainException $domainException) {
            return back()->withErrors(['match' => $domainException->getMessage()]);
        }

        return redirect()->route('matches.show', $match->uuid);
    }
}
