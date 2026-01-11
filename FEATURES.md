# Feature Ideas

Ideas for future development. Discuss with team before implementing.

---

## Team/Group Games (2v2, FFA)

**Status:** Proposed

**Description:**
Support for games with more than 2 players - either team-based (2v2) or free-for-all group games.

**Game Modes:**
| Mode | Players | Winner | ELO Calculation |
|------|---------|--------|-----------------|
| 1v1 | 2 | Individual | Standard ELO |
| 2v2 | 4 | Team | Team average ELO |
| FFA (3+) | 3-8 | Individual | Avg vs all opponents |

**How ELO would work for teams:**
- Team rating = average of all team members
- ELO change calculated using team averages
- Each player gets the same +/- rating change

**Example (2v2):**
- Team A: Player 1 (1200) + Player 2 (1400) = avg 1300
- Team B: Player 3 (1100) + Player 4 (1500) = avg 1300
- If Team A wins: All Team A members get +X, all Team B members get -X

**Database considerations:**
- `match_players` pivot already exists
- Add: `team` column (1, 2, null for FFA)
- Add: `placement` column (1st, 2nd, 3rd... for FFA)
- Game model needs: `min_players`, `max_players`, `team_size`

**UI considerations:**
- Match creation: select game mode, add players to teams
- Match page: show teams visually grouped
- Leaderboard: still individual, games with team modes marked

**Questions for team:**
1. Should this be per-game setting or match-level choice?
2. How to handle FFA with 3+ players - just 1 winner or rank all?
3. Should teams be random or player-selected?

---

## Dark/Light Mode

**Status:** Proposed

**Description:**
Allow admin to configure the app's color mode - light mode, dark mode, or let users choose.

**Options:**
| Setting | Behavior |
|---------|----------|
| Light (default) | Always light mode |
| Dark | Always dark mode |
| User Choice | Toggle in user settings |

**Implementation approach:**
- Add `color_mode` to settings table (values: 'light', 'dark', 'user_choice')
- If 'user_choice', add toggle to user profile
- Use Tailwind's `dark:` classes for styling
- Set `<html class="dark">` based on setting

**Branding consideration:**
- Primary color should work well in both modes
- May need separate `primary_color_dark` setting
- Or auto-calculate lighter/darker variant

**Questions for team:**
1. Should dark mode be admin-only or allow user choice?
2. Need separate primary color for dark mode?

---

## Invite Player to Match

**Status:** Proposed

**Description:**
Instead of sharing a join link, allow players in the room to directly add another user to the match. However, the invited player must confirm/start the match.

**How it would work:**
1. Creator creates match, is in "room"
2. Creator can search/select a user to invite
3. Selected user receives notification
4. Invited user must accept before match can start
5. Only the invited player (or creator) can trigger match start

**Benefits:**
- No need to share links externally
- Prevents random strangers from joining via leaked links
- Creates clear match intent between known players
- Notification system integration opportunity

**Database changes:**
- Add: `invited_by` column to match_players
- Add: `invitation_status` (pending, accepted, declined)
- Match status: `waiting_for_acceptance` before `pending`

**UI Flow:**
```
Creator View:
[Invite Player] button → User search modal → Select user → "Waiting for {name} to accept"

Invited Player View:
Notification: "{name} invited you to play {game}"
[Accept] [Decline] buttons
```

**Admin setting:**
- Toggle: "Allow invite-only matches" (default: allow both invite and link)
- This determines if share link is shown

**Questions for team:**
1. Should both methods (invite + link) coexist?
2. How long before invite expires?
3. Can creator cancel invite?
4. Notification channel - in-app, email, both?

---

## Enhanced User Management (Admin)

**Status:** Proposed

**Description:**
Expand admin user management capabilities beyond just toggling admin status.

**Features:**
| Feature | Description |
|---------|-------------|
| View all users | List with search, filter, pagination |
| Edit user | Change name, email, reset password |
| Deactivate/Ban user | Disable account without deleting |
| Delete user | Remove user and optionally their data |
| View user stats | Matches played, win rate, rating history |
| Impersonate | Login as user for debugging (with audit log) |

**Impersonation Safety:**
- Matches played while impersonating = **test matches** (no ELO applied)
- Visual indicator: "Impersonating {user}" banner
- All actions logged with admin ID + timestamp
- Auto-expire impersonation after X minutes
- Match records show `is_test: true` flag

**Current state:**
- Users list exists (`/admin/users`)
- Toggle admin status works

**Additions needed:**
- Edit user modal/page
- Deactivate toggle (add `is_active` column to users)
- Delete with confirmation
- User detail view with stats

**Questions for team:**
1. Soft delete or hard delete users?
2. What happens to match history when user is deleted?
3. Should deactivated users still appear on leaderboard?

---

## Registration Toggle (Admin)

**Status:** Proposed

**Description:**
Allow admin to enable/disable new user registration. Useful for private/invite-only instances.

**Settings:**
| Option | Behavior |
|--------|----------|
| Open | Anyone can register (default) |
| Closed | Registration disabled, show message |
| Invite Only | Registration requires invite code |

**Implementation:**
- Add `registration_mode` to settings table
- Middleware to check before showing register page
- Custom message setting for closed registration
- Invite code system for invite-only mode

**Invite Code System (if invite-only):**
- Admin generates codes (single-use or multi-use)
- Code required on registration form
- Track who used which code

**UI:**
- Admin Settings page: Registration toggle dropdown
- Register page: Show message if closed, show code field if invite-only

**Questions for team:**
1. Need invite code system or just open/closed?
2. Should existing users be able to invite others?
3. Custom message when registration is closed?

---

## Streak Multiplier System

**Status:** Proposed

**Description:**
Add automatic rating multipliers when playing against someone on a win streak. This creates "boss battle" moments and adds excitement to matches.

**How it would work:**
- When facing an opponent on a win streak, the ELO rating change is multiplied
- Both players affected: winner gains more, loser loses more
- Multiplier scales with streak length

**Proposed multipliers:**
| Streak | Multiplier |
|--------|------------|
| 3-4 wins | 1.25x |
| 5-6 wins | 1.5x |
| 7+ wins | 2x |

**Example:**
- Normal match: Winner +20, Loser -20
- vs 5-streak opponent: Winner +30, Loser -30

**Pros:**
- Adds excitement and stakes to matches
- Rewards players who take down streak holders
- Creates natural "boss battle" narratives
- Streak holder has skin in the game

**Cons:**
- Streak holders might avoid matches (more to lose)
- Could discourage casual play
- Might feel punishing for streak holders who lose

**Implementation notes:**
- `win_streak` already tracked in leaderboard entries
- Would need to modify ELO K-factor calculation
- Show streak badge prominently on match page ("You're facing a 5-streak!")
- Consider: Should this be opt-in or automatic?

**Questions for team:**
1. Should this be automatic or opt-in (challenge mode)?
2. Are the multiplier values balanced?
3. Should there be a cap on maximum multiplier?
4. Should streak holder be able to "protect" their streak by declining?

---
