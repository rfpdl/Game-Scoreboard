import { defineComponent, type PropType } from 'vue';
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Components/atoms/Avatar';
import RatingBadge from '@/Components/atoms/RatingBadge';
import Badge from '@/Components/atoms/Badge';

interface Player {
    id: number;
    name: string;
    avatar?: string | null;
    rating?: number;
    ratingChange?: number | null;
    result?: 'pending' | 'win' | 'lose' | 'draw';
    profileUrl?: string | null;
    isHost?: boolean;
}

export default defineComponent({
    name: 'PlayerCard',
    components: { Link },
    props: {
        player: {
            type: Object as PropType<Player>,
            required: true,
        },
        showResult: {
            type: Boolean,
            default: false,
        },
        isCurrentUser: {
            type: Boolean,
            default: false,
        },
        compact: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const resultVariants = {
            pending: 'default',
            win: 'success',
            lose: 'danger',
            draw: 'warning',
        } as const;

        const resultLabels = {
            pending: 'Pending',
            win: 'Winner',
            lose: 'Lost',
            draw: 'Draw',
        };

        const AvatarElement = () => (
            <Avatar
                name={props.player.name}
                src={props.player.avatar}
                size={props.compact ? 'sm' : 'md'}
            />
        );

        const NameElement = () => (
            <span class={['font-medium truncate text-gray-900 dark:text-white', props.compact ? 'text-sm' : 'text-base']}>
                {props.player.name}
            </span>
        );

        return () => (
            <div
                class={[
                    'flex items-center gap-3 p-3 rounded-lg border',
                    props.isCurrentUser ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-200 dark:border-indigo-800' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600',
                    props.compact ? 'p-2' : 'p-3',
                ]}
            >
                {props.player.profileUrl ? (
                    <Link href={props.player.profileUrl} class="flex-shrink-0 hover:opacity-80 transition-opacity" onClick={(e: Event) => e.stopPropagation()}>
                        <AvatarElement />
                    </Link>
                ) : (
                    <AvatarElement />
                )}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        {props.player.isHost && (
                            <span class="text-yellow-500 flex-shrink-0" title="Match Host">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                                </svg>
                            </span>
                        )}
                        {props.player.profileUrl ? (
                            <Link href={props.player.profileUrl} class="hover:underline" onClick={(e: Event) => e.stopPropagation()}>
                                <NameElement />
                            </Link>
                        ) : (
                            <NameElement />
                        )}
                        {props.isCurrentUser && (
                            <Badge variant="info" size="sm">You</Badge>
                        )}
                    </div>
                    {props.player.rating !== undefined && (
                        <div class="mt-0.5">
                            <RatingBadge
                                rating={props.player.rating}
                                change={props.player.ratingChange}
                                size="sm"
                                showTrend={props.showResult}
                            />
                        </div>
                    )}
                </div>
                {props.showResult && props.player.result && (
                    <Badge variant={resultVariants[props.player.result]} size="sm">
                        {resultLabels[props.player.result]}
                    </Badge>
                )}
            </div>
        );
    },
});
