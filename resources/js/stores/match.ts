import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Match, Game } from '@/types';
import { router } from '@inertiajs/vue3';
import { useToastStore } from './toast';

export const useMatchStore = defineStore('match', () => {
    const currentMatch = ref<Match | null>(null);
    const games = ref<Game[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const isPending = computed(() => currentMatch.value?.status === 'pending');
    const isConfirmed = computed(() => currentMatch.value?.status === 'confirmed');
    const isCompleted = computed(() => currentMatch.value?.status === 'completed');

    const playerCount = computed(() => currentMatch.value?.players.length ?? 0);
    const canConfirm = computed(() => isPending.value && playerCount.value >= 2);

    function setCurrentMatch(match: Match | null) {
        currentMatch.value = match;
    }

    function setGames(gameList: Game[]) {
        games.value = gameList;
    }

    function setLoading(loading: boolean) {
        isLoading.value = loading;
    }

    function setError(err: string | null) {
        error.value = err;
    }

    interface CreateMatchParams {
        gameId: number;
        matchType?: 'quick' | 'booked';
        matchFormat?: '1v1' | '2v2' | '3v3' | '4v4' | 'ffa';
        maxPlayers?: number;
        name?: string;
        scheduledAt?: string;
    }

    function createMatch(params: CreateMatchParams) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.store'),
            {
                game_id: params.gameId,
                match_type: params.matchType || 'quick',
                match_format: params.matchFormat || '1v1',
                max_players: params.maxPlayers,
                name: params.name,
                scheduled_at: params.scheduledAt,
            },
            {
                onSuccess: () => {
                    setLoading(false);
                },
                onError: (errors) => {
                    setError(errors.game_id || errors.match_type || errors.match_format || errors.scheduled_at || 'Failed to create match');
                    setLoading(false);
                },
            }
        );
    }

    function joinMatch(code: string) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.joinByCode'),
            { code },
            {
                onSuccess: () => {
                    setLoading(false);
                },
                onError: (errors) => {
                    setError(errors.code || 'Failed to join match');
                    setLoading(false);
                },
            }
        );
    }

    function confirmMatch(uuid: string) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.confirm', { uuid }),
            {},
            {
                onSuccess: () => {
                    setLoading(false);
                    // Toast handled by WebSocket listener for all players
                },
                onError: (errors) => {
                    setError(errors.match || 'Failed to confirm match');
                    setLoading(false);
                    const toast = useToastStore();
                    toast.error(errors.match || 'Failed to start match');
                },
            }
        );
    }

    function completeMatch(uuid: string, winnerId: number) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.complete', { uuid }),
            { winner_id: winnerId },
            {
                onSuccess: () => {
                    setLoading(false);
                    // Toast handled by WebSocket listener for all players
                },
                onError: (errors) => {
                    setError(errors.match || errors.winner_id || 'Failed to complete match');
                    setLoading(false);
                    const toast = useToastStore();
                    toast.error(errors.match || errors.winner_id || 'Failed to complete match');
                },
            }
        );
    }

    function cancelMatch(uuid: string) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.cancel', { uuid }),
            {},
            {
                onSuccess: () => {
                    setLoading(false);
                    // Toast handled by WebSocket listener for all players
                },
                onError: (errors) => {
                    setError(errors.match || 'Failed to cancel match');
                    setLoading(false);
                    const toast = useToastStore();
                    toast.error(errors.match || 'Failed to cancel match');
                },
            }
        );
    }

    function leaveMatch(uuid: string) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.leave', { uuid }),
            {},
            {
                onSuccess: () => {
                    setLoading(false);
                    // Toast handled by WebSocket listener for all players
                },
                onError: (errors) => {
                    setError(errors.match || 'Failed to leave match');
                    setLoading(false);
                    const toast = useToastStore();
                    toast.error(errors.match || 'Failed to leave match');
                },
            }
        );
    }

    function switchTeam(uuid: string) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.switchTeam', { uuid }),
            {},
            {
                onSuccess: () => {
                    setLoading(false);
                    // Toast handled by WebSocket listener for all players
                },
                onError: (errors) => {
                    setError(errors.match || 'Failed to switch team');
                    setLoading(false);
                    const toast = useToastStore();
                    toast.error(errors.match || 'Failed to switch team');
                },
            }
        );
    }

    function changeFormat(uuid: string, format: string) {
        setLoading(true);
        setError(null);

        router.post(
            route('matches.changeFormat', { uuid }),
            { format },
            {
                onSuccess: () => {
                    setLoading(false);
                    // Toast handled by WebSocket listener for all players
                },
                onError: (errors) => {
                    setError(errors.match || errors.format || 'Failed to change format');
                    setLoading(false);
                    const toast = useToastStore();
                    toast.error(errors.match || errors.format || 'Failed to change format');
                },
            }
        );
    }

    function reset() {
        currentMatch.value = null;
        error.value = null;
        isLoading.value = false;
    }

    return {
        // State
        currentMatch,
        games,
        isLoading,
        error,

        // Computed
        isPending,
        isConfirmed,
        isCompleted,
        playerCount,
        canConfirm,

        // Actions
        setCurrentMatch,
        setGames,
        setLoading,
        setError,
        createMatch,
        joinMatch,
        confirmMatch,
        completeMatch,
        cancelMatch,
        leaveMatch,
        switchTeam,
        changeFormat,
        reset,
    };
});
