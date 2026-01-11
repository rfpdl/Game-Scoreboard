import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { LeaderboardEntry, Game } from '@/types';

export const useLeaderboardStore = defineStore('leaderboard', () => {
    const entries = ref<LeaderboardEntry[]>([]);
    const selectedGameId = ref<number | null>(null);
    const games = ref<Game[]>([]);
    const isLoading = ref(false);

    const topPlayers = computed(() => entries.value.slice(0, 10));

    const currentUserRank = (userId: number) => {
        return entries.value.find((e) => e.userId === userId);
    };

    const selectedGame = computed(() => {
        return games.value.find((g) => g.id === selectedGameId.value);
    });

    function setEntries(data: LeaderboardEntry[]) {
        entries.value = data;
    }

    function setGames(gameList: Game[]) {
        games.value = gameList;
        if (!selectedGameId.value && gameList.length > 0 && gameList[0].id) {
            selectedGameId.value = gameList[0].id;
        }
    }

    function selectGame(gameId: number | null) {
        selectedGameId.value = gameId;
    }

    function setLoading(loading: boolean) {
        isLoading.value = loading;
    }

    function reset() {
        entries.value = [];
        selectedGameId.value = null;
        isLoading.value = false;
    }

    return {
        // State
        entries,
        selectedGameId,
        games,
        isLoading,

        // Computed
        topPlayers,
        currentUserRank,
        selectedGame,

        // Actions
        setEntries,
        setGames,
        selectGame,
        setLoading,
        reset,
    };
});
