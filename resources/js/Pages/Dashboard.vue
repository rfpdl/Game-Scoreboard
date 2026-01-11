<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { PlayerRating, Match, Game, PageProps } from '@/types';
import MatchCard from '@/Components/molecules/MatchCard';
import RatingBadge from '@/Components/atoms/RatingBadge';
import Button from '@/Components/atoms/Button';
import { router } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

interface Props {
    ratings: PlayerRating[];
    recentMatches: Match[];
    pendingMatches: Match[];
    games: Game[];
}

const props = defineProps<Props>();
const { primaryColor, primaryColorHover, primaryColorLight } = useBranding();

const navigateToMatch = (match: Match) => {
    router.visit(route('matches.show', { uuid: match.uuid }));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                Dashboard
            </h2>
        </template>

        <div class="py-6 px-4 sm:py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-4 sm:space-y-6">
                <!-- Leaderboard - Prominent (Desktop only, mobile uses bottom nav) -->
                <Link :href="route('leaderboard.index')" class="hidden sm:block group">
                    <div
                        class="overflow-hidden shadow-lg rounded-xl p-5 sm:p-6 text-white transition-all duration-200 hover:shadow-xl hover:scale-[1.01] hover:ring-2 hover:ring-white/30"
                        :style="{ backgroundColor: primaryColor }"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 sm:gap-4">
                                <div class="bg-white/20 rounded-full p-2.5 sm:p-3">
                                    <!-- Trophy Icon -->
                                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M5.166 2.621v.858c-1.035.148-2.059.33-3.071.543a.75.75 0 00-.584.859 6.753 6.753 0 006.138 5.6 6.73 6.73 0 002.743 1.346A6.707 6.707 0 019.279 15H8.54c-1.036 0-1.875.84-1.875 1.875V19.5h-.75a.75.75 0 000 1.5h12.17a.75.75 0 000-1.5h-.75v-2.625c0-1.036-.84-1.875-1.875-1.875h-.739a6.707 6.707 0 01-1.112-3.173 6.73 6.73 0 002.743-1.347 6.753 6.753 0 006.139-5.6.75.75 0 00-.585-.858 47.077 47.077 0 00-3.07-.543V2.62a.75.75 0 00-.658-.744 49.22 49.22 0 00-6.093-.377c-2.063 0-4.096.128-6.093.377a.75.75 0 00-.657.744zm0 2.629c0 1.196.312 2.32.857 3.294A5.266 5.266 0 013.16 5.337a45.6 45.6 0 012.006-.343v.256zm13.5 0v-.256c.674.1 1.343.214 2.006.343a5.265 5.265 0 01-2.863 3.207 6.72 6.72 0 00.857-3.294z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg sm:text-xl font-bold">Leaderboard</h3>
                                    <p class="text-white/80 text-sm">See rankings & stats</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </Link>

                <!-- Quick Actions (Desktop only, mobile uses bottom nav) -->
                <div class="hidden sm:grid grid-cols-2 gap-3 sm:gap-4">
                    <Link :href="route('matches.create')" class="block group">
                        <div
                            class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6 h-full border-2 border-transparent transition-all duration-200 hover:shadow-lg hover:scale-[1.02]"
                            :style="{ '--hover-border': primaryColor }"
                            style="border-color: transparent;"
                            @mouseenter="($event.currentTarget as HTMLElement).style.borderColor = primaryColor"
                            @mouseleave="($event.currentTarget as HTMLElement).style.borderColor = 'transparent'"
                        >
                            <div class="flex flex-col items-center text-center gap-2 sm:gap-3">
                                <div class="rounded-full p-3 transition-transform duration-200 group-hover:scale-110" :style="{ backgroundColor: primaryColorLight }">
                                    <svg class="w-6 h-6 sm:w-7 sm:h-7" :style="{ color: primaryColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">Create Match</span>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('matches.join')" class="block group">
                        <div
                            class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl p-4 sm:p-6 h-full border-2 border-transparent transition-all duration-200 hover:shadow-lg hover:scale-[1.02] hover:border-gray-300 dark:hover:border-gray-500"
                        >
                            <div class="flex flex-col items-center text-center gap-2 sm:gap-3">
                                <div class="bg-gray-100 dark:bg-gray-700 rounded-full p-3 transition-transform duration-200 group-hover:scale-110">
                                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">Join Match</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pending Matches -->
                <div v-if="pendingMatches.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-3 sm:mb-4">Pending Matches</h3>
                        <div class="space-y-2 sm:space-y-3">
                            <MatchCard
                                v-for="match in pendingMatches"
                                :key="match.uuid"
                                :match="match"
                                @click="navigateToMatch"
                            />
                        </div>
                    </div>
                </div>

                <!-- Recent Matches -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl">
                    <div class="p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-white mb-3 sm:mb-4">Recent Matches</h3>
                        <div v-if="recentMatches.length > 0" class="space-y-2 sm:space-y-3">
                            <MatchCard
                                v-for="match in recentMatches"
                                :key="match.uuid"
                                :match="match"
                                @click="navigateToMatch"
                            />
                        </div>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-center py-6 sm:py-8 text-sm sm:text-base">
                            No matches yet. Create your first match to get started!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
