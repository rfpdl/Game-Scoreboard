import { defineComponent, type PropType, computed } from 'vue';
import Badge from '@/Components/atoms/Badge';
import Avatar from '@/Components/atoms/Avatar';
import type { Match } from '@/types';

type BadgeVariant = 'default' | 'success' | 'warning' | 'danger' | 'info';

export default defineComponent({
    name: 'MatchCard',
    props: {
        match: {
            type: Object as PropType<Match>,
            required: true,
        },
        currentUserId: {
            type: Number as PropType<number | null>,
            default: null,
        },
    },
    emits: ['click'],
    setup(props, { emit }) {
        const statusVariants: Record<string, BadgeVariant> = {
            pending: 'warning',
            confirmed: 'info',
            completed: 'success',
            cancelled: 'danger',
        };

        const statusLabels: Record<string, string> = {
            pending: 'Pending',
            confirmed: 'In Progress',
            completed: 'Completed',
            cancelled: 'Cancelled',
        };

        const winner = computed(() => {
            return props.match.players.find(p => p.result === 'win');
        });

        const loser = computed(() => {
            return props.match.players.find(p => p.result === 'lose');
        });

        const formattedDate = computed(() => {
            if (!props.match.playedAt) return null;
            return new Date(props.match.playedAt).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
        });

        return () => (
            <div
                class="bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 hover:border-gray-400 dark:hover:border-gray-400 hover:shadow-md transition-all duration-200 cursor-pointer hover:scale-[1.01]"
                onClick={() => emit('click', props.match)}
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        {props.match.gameIcon && (
                            <span class="text-base">{props.match.gameIcon}</span>
                        )}
                        <span class="font-medium text-gray-900 dark:text-white">{props.match.gameName}</span>
                        <Badge variant={statusVariants[props.match.status]} size="sm">
                            {statusLabels[props.match.status]}
                        </Badge>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{props.match.matchCode}</span>
                </div>

                {props.match.status === 'completed' && winner.value && loser.value ? (
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 flex-1">
                            <Avatar
                                name={winner.value.userName ?? 'Unknown'}
                                src={winner.value.userAvatar}
                                size="sm"
                            />
                            <div class="min-w-0">
                                <div class="font-medium text-sm text-green-600 dark:text-green-400 truncate">
                                    {winner.value.userName}
                                </div>
                                <div class="text-xs text-green-600 dark:text-green-400">
                                    {winner.value.ratingAfter} (+{winner.value.ratingChange})
                                </div>
                            </div>
                        </div>
                        <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">vs</span>
                        <div class="flex items-center gap-2 flex-1 justify-end">
                            <div class="min-w-0 text-right">
                                <div class="font-medium text-sm text-gray-700 dark:text-gray-300 truncate">
                                    {loser.value.userName}
                                </div>
                                <div class="text-xs text-red-600 dark:text-red-400">
                                    {loser.value.ratingAfter} ({loser.value.ratingChange})
                                </div>
                            </div>
                            <Avatar
                                name={loser.value.userName ?? 'Unknown'}
                                src={loser.value.userAvatar}
                                size="sm"
                            />
                        </div>
                    </div>
                ) : props.match.players.length === 2 ? (
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 flex-1">
                            <Avatar
                                name={props.match.players[0].userName ?? 'Unknown'}
                                src={props.match.players[0].userAvatar}
                                size="sm"
                            />
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-200 truncate">
                                {props.match.players[0].userName}
                            </span>
                        </div>
                        <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">vs</span>
                        <div class="flex items-center gap-2 flex-1 justify-end">
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-200 truncate">
                                {props.match.players[1].userName}
                            </span>
                            <Avatar
                                name={props.match.players[1].userName ?? 'Unknown'}
                                src={props.match.players[1].userAvatar}
                                size="sm"
                            />
                        </div>
                    </div>
                ) : (
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 flex-1">
                            <Avatar
                                name={props.match.players[0]?.userName ?? 'Unknown'}
                                src={props.match.players[0]?.userAvatar}
                                size="sm"
                            />
                            <span class="font-medium text-sm text-gray-700 dark:text-gray-200 truncate">
                                {props.match.players[0]?.userName}
                            </span>
                        </div>
                        <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">vs</span>
                        <div class="flex items-center gap-2 flex-1 justify-end text-gray-400 dark:text-gray-500">
                            <span class="text-sm">Waiting...</span>
                            <div class="h-8 w-8 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center">
                                <span class="text-xs">?</span>
                            </div>
                        </div>
                    </div>
                )}

                {formattedDate.value && (
                    <div class="mt-3 text-xs text-gray-500">
                        Played {formattedDate.value}
                    </div>
                )}
            </div>
        );
    },
});
