<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index(): Response
    {
        $users = User::orderBy('name')
            ->withCount(['ratings', 'matchPlayers'])
            ->get()
            ->map(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'nickname' => $user->nickname,
                'displayName' => $user->display_name,
                'email' => $user->email,
                'isAdmin' => $user->is_admin,
                'isDisabled' => $user->is_disabled,
                'ratingsCount' => $user->ratings_count,
                'matchesCount' => $user->match_players_count,
                'createdAt' => $user->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
            'is_disabled' => false,
        ]);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'created',
            subjectType: 'user',
            subjectId: $user->id,
            subjectName: $user->name,
            ipAddress: $request->ip(),
        );

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update user details.
     */
    public function update(User $user, Request $request): RedirectResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,'.$user->id,
        ];

        // Only validate password if provided
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'updated',
            subjectType: 'user',
            subjectId: $user->id,
            subjectName: $user->name,
            changes: [
                'fields' => array_keys($updateData),
            ],
            ipAddress: $request->ip(),
        );

        return redirect()->back()->with('success', 'User updated.');
    }

    /**
     * Toggle admin status.
     */
    public function toggleAdmin(User $user, Request $request): RedirectResponse
    {
        // Prevent removing admin status from yourself
        if ($user->id === $request->user()->id) {
            return redirect()->back()->withErrors(['error' => 'Cannot modify your own admin status.']);
        }

        $oldValue = $user->is_admin;
        $user->update(['is_admin' => ! $user->is_admin]);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'toggled_admin',
            subjectType: 'user',
            subjectId: $user->id,
            subjectName: $user->name,
            changes: [
                'old' => $oldValue,
                'new' => $user->is_admin,
            ],
            ipAddress: $request->ip(),
        );

        return redirect()->back();
    }

    /**
     * Toggle disabled status.
     */
    public function toggleDisabled(User $user, Request $request): RedirectResponse
    {
        // Prevent disabling yourself
        if ($user->id === $request->user()->id) {
            return redirect()->back()->withErrors(['error' => 'Cannot disable your own account.']);
        }

        $oldValue = $user->is_disabled;
        $user->update(['is_disabled' => ! $user->is_disabled]);

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'toggled_disabled',
            subjectType: 'user',
            subjectId: $user->id,
            subjectName: $user->name,
            changes: [
                'old' => $oldValue,
                'new' => $user->is_disabled,
            ],
            ipAddress: $request->ip(),
        );

        return redirect()->back()->with('success', $user->is_disabled ? 'User disabled.' : 'User enabled.');
    }

    /**
     * Remove user and clean up their ratings/match participation.
     */
    public function destroy(User $user, Request $request): RedirectResponse
    {
        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete your own account.']);
        }

        $userName = $user->name;
        $userId = $user->id;

        DB::transaction(function () use ($user): void {
            // Delete user's ratings
            $user->ratings()->delete();

            // Remove user from match players
            $user->matchPlayers()->delete();

            // Cancel any pending matches created by this user
            $user->createdMatches()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            // Keep created_by_user_id reference - display will show "User Deleted"
            // since we use soft deletes, the reference remains valid

            // Soft delete the user
            $user->delete();
        });

        ActivityLog::log(
            userId: $request->user()->id,
            action: 'deleted',
            subjectType: 'user',
            subjectId: $userId,
            subjectName: $userName,
            ipAddress: $request->ip(),
        );

        return redirect()->route('admin.users.index')->with('success', 'User removed and all their ratings have been deleted.');
    }
}
