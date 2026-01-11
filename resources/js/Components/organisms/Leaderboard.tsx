import { defineComponent, type PropType } from 'vue';
import Avatar from '@/Components/atoms/Avatar';
import RatingBadge from '@/Components/atoms/RatingBadge';
import Badge from '@/Components/atoms/Badge';
import type { LeaderboardEntry } from '@/types';

export default defineComponent({
    name: 'Leaderboard',
    props: {
        entries: {
            type: Array as PropType<LeaderboardEntry[]>,
            required: true,
        },
        currentUserId: {
            type: Number as PropType<number | null>,
            default: null,
        },
        title: {
            type: String,
            default: 'Leaderboard',
        },
        showStats: {
            type: Boolean,
            default: true,
        },
        gameSlug: {
            type: String as PropType<string | null>,
            default: null,
        },
    },
    emits: ['viewHistory'],
    setup(props, { emit }) {
        const getRankStyle = (rank: number) => {
            if (rank === 1) return 'bg-yellow-100 text-yellow-800 border-yellow-300';
            if (rank === 2) return 'bg-gray-100 text-gray-800 border-gray-300';
            if (rank === 3) return 'bg-orange-100 text-orange-800 border-orange-300';
            return 'bg-white text-gray-600 border-gray-200';
        };

        const getRankIcon = (rank: number) => {
            if (rank === 1) return '1st';
            if (rank === 2) return '2nd';
            if (rank === 3) return '3rd';
            return `#${rank}`;
        };

        const handleViewHistory = (entry: LeaderboardEntry) => {
            emit('viewHistory', entry);
        };

        return () => (
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{props.title}</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    {props.entries.length === 0 ? (
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            No players yet. Be the first to play!
                        </div>
                    ) : (
                        props.entries.map((entry) => {
                            const isCurrentUser = entry.userId === props.currentUserId;
                            return (
                                <div
                                    key={entry.userId}
                                    class={[
                                        'p-3 sm:p-4 transition-colors',
                                        isCurrentUser ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700',
                                    ]}
                                >
                                    {/* Mobile Layout */}
                                    <div class="flex items-center gap-2 sm:hidden">
                                        {/* Rank */}
                                        <div
                                            class={[
                                                'flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold border-2',
                                                getRankStyle(entry.rank),
                                            ]}
                                        >
                                            {entry.rank}
                                        </div>

                                        {/* Name and Stats */}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1">
                                                <span class="font-medium text-gray-900 dark:text-white truncate text-sm">
                                                    {entry.userName}
                                                </span>
                                                {isCurrentUser && (
                                                    <Badge variant="info" size="sm">You</Badge>
                                                )}
                                            </div>
                                            {props.showStats && (
                                                <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-gray-500">
                                                    <span>{entry.matchesPlayed}G</span>
                                                    <span class="text-green-600">{entry.wins}W</span>
                                                    <span class="text-red-600">{entry.losses}L</span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Rating */}
                                        <div class="flex-shrink-0 text-right">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{entry.rating}</div>
                                            {props.gameSlug && (
                                                <button
                                                    onClick={() => handleViewHistory(entry)}
                                                    class="text-[10px] text-indigo-600 font-medium"
                                                >
                                                    History
                                                </button>
                                            )}
                                        </div>
                                    </div>

                                    {/* Desktop Layout */}
                                    <div class="hidden sm:flex items-center gap-4">
                                        <div
                                            class={[
                                                'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2',
                                                getRankStyle(entry.rank),
                                            ]}
                                        >
                                            {getRankIcon(entry.rank)}
                                        </div>

                                        <Avatar
                                            name={entry.userName}
                                            src={entry.userAvatar}
                                            size="md"
                                        />

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    onClick={() => handleViewHistory(entry)}
                                                    class="font-medium text-gray-900 dark:text-white truncate hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline transition-colors text-left"
                                                >
                                                    {entry.userName}
                                                </button>
                                                {isCurrentUser && (
                                                    <Badge variant="info" size="sm">You</Badge>
                                                )}
                                                {entry.winStreak >= 3 && (
                                                    <Badge variant="warning" size="sm">
                                                        {entry.winStreak} streak
                                                    </Badge>
                                                )}
                                            </div>
                                            {props.showStats && (
                                                <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    <span>{entry.matchesPlayed} games</span>
                                                    <span class="text-green-600 dark:text-green-400">{entry.wins}W</span>
                                                    <span class="text-red-600 dark:text-red-400">{entry.losses}L</span>
                                                    <span>{entry.winRate}% win rate</span>
                                                </div>
                                            )}
                                        </div>

                                        <div class="flex items-center gap-3 flex-shrink-0">
                                            <RatingBadge rating={entry.rating} size="md" showTrend={false} />
                                            {props.gameSlug && (
                                                <button
                                                    onClick={() => handleViewHistory(entry)}
                                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                                >
                                                    History
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        );
    },
});
