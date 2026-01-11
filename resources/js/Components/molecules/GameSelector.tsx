import { defineComponent, type PropType } from 'vue';
import type { Game } from '@/types';

export default defineComponent({
    name: 'GameSelector',
    props: {
        games: {
            type: Array as PropType<Game[]>,
            required: true,
        },
        modelValue: {
            type: Number as PropType<number | null>,
            default: null,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const handleSelect = (gameId: number) => {
            if (!props.disabled) {
                emit('update:modelValue', gameId);
            }
        };

        return () => (
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                {props.games.map((game) => (
                    <button
                        key={game.id}
                        type="button"
                        data-testid={`game-card-${game.slug}`}
                        disabled={props.disabled || !game.isActive}
                        onClick={() => game.id && handleSelect(game.id)}
                        class={[
                            'relative flex flex-col items-center p-4 rounded-lg border-2 transition-all',
                            props.modelValue === game.id
                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-200 dark:ring-indigo-800'
                                : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500',
                            (props.disabled || !game.isActive) && 'opacity-50 cursor-not-allowed',
                        ]}
                    >
                        {game.icon ? (
                            <span class="text-3xl mb-2">{game.icon}</span>
                        ) : (
                            <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center mb-2">
                                <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">
                                    {game.name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                        )}
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{game.name}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {game.minPlayers === game.maxPlayers
                                ? `${game.minPlayers} players`
                                : `${game.minPlayers}-${game.maxPlayers} players`}
                        </span>
                        {props.modelValue === game.id && (
                            <div class="absolute top-2 right-2">
                                <svg class="h-5 w-5 text-indigo-500 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        )}
                    </button>
                ))}
            </div>
        );
    },
});
