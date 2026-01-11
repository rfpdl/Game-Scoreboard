<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { LeaderboardEntry, Game, User } from '@/types';
import Leaderboard from '@/Components/organisms/Leaderboard';
import Button from '@/Components/atoms/Button';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

const { appName, logoUrl, hasLogoImage } = useBranding();

interface Props {
    entries: LeaderboardEntry[];
    games: Game[];
    selectedGame: Game | null;
}

const props = defineProps<Props>();
const page = usePage();

const currentUser = computed(() => page.props.auth?.user as User | undefined);

const selectGame = (gameId: number | null) => {
    if (gameId) {
        const game = props.games.find(g => g.id === gameId);
        if (game) {
            router.visit(route('leaderboard.index', { game: game.slug }), {
                preserveState: true,
                preserveScroll: true,
            });
        }
    } else {
        router.visit(route('leaderboard.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const viewPlayerHistory = (entry: LeaderboardEntry) => {
    if (props.selectedGame) {
        router.visit(route('leaderboard.player.matches', {
            game: props.selectedGame.slug,
            user: entry.userId,
        }));
    }
};
</script>

<template>
    <Head title="Leaderboard" />

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('dashboard')" class="flex items-center gap-2">
                            <img
                                v-if="hasLogoImage"
                                :src="logoUrl"
                                :alt="appName"
                                class="h-8 w-auto dark:invert"
                            />
                            <span v-else class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ appName }}
                            </span>
                        </Link>
                    </div>
                    <div class="flex items-center gap-4">
                        <template v-if="currentUser">
                            <Link :href="route('dashboard')">
                                <Button variant="ghost" size="sm">Dashboard</Button>
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')">
                                <Button variant="ghost" size="sm">Login</Button>
                            </Link>
                            <Link :href="route('register')">
                                <Button variant="primary" size="sm">Sign Up</Button>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span v-if="selectedGame && selectedGame.icon" class="text-3xl">{{ selectedGame.icon }}</span>
                    {{ selectedGame ? `${selectedGame.name} Leaderboard` : 'Leaderboard' }}
                </h1>
            </div>
        </header>

        <main class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Game Filter -->
                <div class="mb-4 sm:mb-6 flex flex-wrap gap-2">
                    <button
                        v-for="game in games"
                        :key="game.id"
                        @click="selectGame(game.id)"
                        :class="[
                            'px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors',
                            selectedGame?.id === game.id
                                ? 'btn-primary text-white'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'
                        ]"
                    >
                        <span v-if="game.icon" class="mr-1">{{ game.icon }}</span>{{ game.name }}
                    </button>
                </div>

                <!-- Leaderboard -->
                <Leaderboard
                    :entries="entries"
                    :current-user-id="currentUser?.id"
                    :title="selectedGame ? `${selectedGame.name} Rankings` : 'Overall Rankings'"
                    :game-slug="selectedGame?.slug"
                    @view-history="viewPlayerHistory"
                />

                <!-- Empty State -->
                <div v-if="entries.length === 0 && selectedGame" class="text-center py-12">
                    <p class="text-gray-500">No players have played {{ selectedGame.name }} yet.</p>
                    <Link v-if="currentUser" :href="route('matches.create')" class="mt-4 inline-block">
                        <Button variant="primary">Be the First to Play</Button>
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
