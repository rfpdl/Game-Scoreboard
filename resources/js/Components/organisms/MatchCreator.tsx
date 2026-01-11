import { defineComponent, type PropType, ref, computed } from 'vue';
import Button from '@/Components/atoms/Button';
import GameSelector from '@/Components/molecules/GameSelector';
import type { Game } from '@/types';

export type MatchFormat = '1v1' | '2v2' | '3v3' | '4v4' | 'ffa';

export interface CreateMatchParams {
    gameId: number;
    matchType: 'quick' | 'booked';
    matchFormat: MatchFormat;
    maxPlayers?: number;
    name?: string;
    scheduledAt?: string;
}

export default defineComponent({
    name: 'MatchCreator',
    props: {
        games: {
            type: Array as PropType<Game[]>,
            required: true,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['create'],
    setup(props, { emit }) {
        const selectedGameId = ref<number | null>(null);
        const matchName = ref('');
        const matchFormat = ref<MatchFormat>('1v1');
        const ffaPlayerCount = ref(4);

        const selectedGame = computed(() => {
            return props.games.find((g) => g.id === selectedGameId.value);
        });

        const canCreate = computed(() => {
            return selectedGameId.value && !props.loading;
        });

        const maxPlayers = computed(() => {
            switch (matchFormat.value) {
                case '1v1': return 2;
                case '2v2': return 4;
                case '3v3': return 6;
                case '4v4': return 8;
                case 'ffa': return ffaPlayerCount.value;
                default: return 2;
            }
        });

        const formatOptions = [
            { value: '1v1' as MatchFormat, label: '1 vs 1', description: 'Two players compete head-to-head' },
            { value: '2v2' as MatchFormat, label: '2 vs 2', description: 'Two teams of 2 players each' },
            { value: '3v3' as MatchFormat, label: '3 vs 3', description: 'Two teams of 3 players each' },
            { value: '4v4' as MatchFormat, label: '4 vs 4', description: 'Two teams of 4 players each' },
            { value: 'ffa' as MatchFormat, label: 'Free For All', description: 'Multiple players compete individually' },
        ];

        const handleCreate = () => {
            if (selectedGameId.value) {
                const params: CreateMatchParams = {
                    gameId: selectedGameId.value,
                    matchType: 'quick',
                    matchFormat: matchFormat.value,
                    maxPlayers: maxPlayers.value,
                    name: matchName.value || undefined,
                };
                emit('create', params);
            }
        };

        return () => (
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Create New Match</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Select a game and match options
                    </p>
                </div>
                <div class="p-4 space-y-4">
                    {/* Game Selection */}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select Game
                        </label>
                        <GameSelector
                            games={props.games}
                            v-model={selectedGameId.value}
                            onUpdate:modelValue={(v) => (selectedGameId.value = v)}
                            disabled={props.loading}
                        />
                    </div>

                    {selectedGame.value && (
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700 p-3">
                            <div class="flex items-center gap-3">
                                {selectedGame.value.icon && (
                                    <span class="text-2xl">{selectedGame.value.icon}</span>
                                )}
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {selectedGame.value.name}
                                    </h4>
                                    {selectedGame.value.description && (
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {selectedGame.value.description}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Match Format */}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Match Format
                        </label>
                        <div class="grid grid-cols-5 gap-2">
                            {formatOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => (matchFormat.value = option.value)}
                                    class={[
                                        'px-2 py-2 rounded-lg border text-xs sm:text-sm font-medium transition-colors',
                                        matchFormat.value === option.value
                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                                            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600',
                                    ].join(' ')}
                                    disabled={props.loading}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                        {/* Format Description */}
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {formatOptions.find((o) => o.value === matchFormat.value)?.description}
                            </p>
                            {matchFormat.value !== '1v1' && matchFormat.value !== 'ffa' && (
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Players will be automatically assigned to Team A or Team B as they join.
                                </p>
                            )}
                            {matchFormat.value === 'ffa' && (
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Each player competes individually. Rankings are based on final placement.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* FFA Player Count */}
                    {matchFormat.value === 'ffa' && (
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Number of Players
                            </label>
                            <select
                                value={ffaPlayerCount.value}
                                onChange={(e) => (ffaPlayerCount.value = parseInt((e.target as HTMLSelectElement).value))}
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                disabled={props.loading}
                            >
                                {[3, 4, 5, 6, 7, 8].map((n) => (
                                    <option key={n} value={n}>
                                        {n} Players
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {/* Match Name (Optional) */}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Match Name <span class="text-gray-400 dark:text-gray-500 font-normal">(optional)</span>
                        </label>
                        <input
                            type="text"
                            v-model={matchName.value}
                            onInput={(e) => (matchName.value = (e.target as HTMLInputElement).value)}
                            placeholder="e.g., Friday Pool Session"
                            maxlength="100"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            disabled={props.loading}
                        />
                    </div>

                    <div class="pt-2">
                        <Button
                            variant="primary"
                            size="lg"
                            disabled={!canCreate.value}
                            loading={props.loading}
                            onClick={handleCreate}
                            class="w-full"
                            data-testid="create-match-button"
                        >
                            {props.loading ? 'Creating Match...' : 'Create Match'}
                        </Button>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                        A code and shareable link will be generated for your opponent to join
                    </p>
                </div>
            </div>
        );
    },
});
