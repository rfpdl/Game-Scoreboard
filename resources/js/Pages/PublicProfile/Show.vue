<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useBranding } from '@/composables/useBranding';

interface Profile {
    name: string;
    nickname: string;
    avatar: string | null;
}

interface Stats {
    matchesPlayed: number;
    matchesWon: number;
    matchesLost: number;
    winRate: number;
}

interface Rating {
    gameId: number;
    gameName: string;
    gameIcon: string | null;
    rating: number;
    matchesPlayed: number;
    wins: number;
    losses: number;
    winStreak: number;
    winRate: number;
}

interface RecentMatch {
    id: number;
    uuid: string;
    game: {
        name: string;
        icon: string | null;
    };
    opponent: {
        name: string;
        avatar: string | null;
    } | null;
    result: 'win' | 'lose' | 'draw' | 'pending';
    ratingChange: number | null;
    playedAt: string | null;
}

interface Props {
    profile: Profile;
    stats: Stats;
    ratings: Rating[];
    recentMatches: RecentMatch[];
}

const props = defineProps<Props>();
const { appName, logoUrl, hasLogoImage, primaryColor } = useBranding();

const getResultColor = (result: string) => {
    if (result === 'win') return 'text-green-600';
    if (result === 'lose') return 'text-red-600';
    return 'text-gray-600';
};

const getResultText = (result: string) => {
    if (result === 'win') return 'Won';
    if (result === 'lose') return 'Lost';
    if (result === 'draw') return 'Draw';
    return 'Pending';
};
</script>

<template>
    <Head :title="`${profile.name} (@${profile.nickname})`" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <nav class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center gap-2">
                        <img v-if="hasLogoImage" :src="logoUrl" :alt="appName" class="h-8 w-auto" />
                        <span v-else class="text-xl font-bold text-gray-900">{{ appName }}</span>
                    </Link>
                    <Link
                        :href="route('leaderboard.index')"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        View Leaderboard
                    </Link>
                </div>
            </div>
        </nav>

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                        <div
                            v-if="profile.avatar"
                            class="w-24 h-24 sm:w-32 sm:h-32 rounded-full overflow-hidden ring-4 ring-gray-100"
                        >
                            <img :src="profile.avatar" :alt="profile.name" class="w-full h-full object-cover" />
                        </div>
                        <div
                            v-else
                            class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-gray-200 flex items-center justify-center ring-4 ring-gray-100"
                        >
                            <span class="text-3xl sm:text-4xl font-bold text-gray-400">
                                {{ profile.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ profile.name }}</h1>
                        <p class="text-gray-500 mt-1">@{{ profile.nickname }}</p>

                        <!-- Stats -->
                        <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-4 sm:gap-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ stats.matchesPlayed }}</div>
                                <div class="text-xs text-gray-500">Matches</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ stats.matchesWon }}</div>
                                <div class="text-xs text-gray-500">Wins</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600">{{ stats.matchesLost }}</div>
                                <div class="text-xs text-gray-500">Losses</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold" :style="{ color: primaryColor }">{{ stats.winRate }}%</div>
                                <div class="text-xs text-gray-500">Win Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ratings by Game -->
            <div v-if="ratings.length > 0" class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="font-semibold text-gray-900">Ratings by Game</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="rating in ratings"
                        :key="rating.gameId"
                        class="px-6 py-4 flex items-center gap-4"
                    >
                        <span v-if="rating.gameIcon" class="text-2xl">{{ rating.gameIcon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900">{{ rating.gameName }}</div>
                            <div class="flex items-center gap-3 text-sm text-gray-500 mt-0.5">
                                <span>{{ rating.matchesPlayed }} games</span>
                                <span class="text-green-600">{{ rating.wins }}W</span>
                                <span class="text-red-600">{{ rating.losses }}L</span>
                                <span>{{ rating.winRate }}% win</span>
                                <span v-if="rating.winStreak >= 3" class="text-amber-600">{{ rating.winStreak }} streak</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold text-white"
                                :style="{ backgroundColor: primaryColor }"
                            >
                                {{ rating.rating }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Matches -->
            <div v-if="recentMatches.length > 0" class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="font-semibold text-gray-900">Recent Matches</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="match in recentMatches"
                        :key="match.id"
                        class="px-6 py-4 flex items-center gap-4"
                    >
                        <span v-if="match.game.icon" class="text-xl">{{ match.game.icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ match.game.name }}</span>
                                <span class="text-gray-400">vs</span>
                                <span class="text-gray-700">{{ match.opponent?.name || 'Unknown' }}</span>
                            </div>
                            <div class="text-sm text-gray-500 mt-0.5">{{ match.playedAt }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                v-if="match.ratingChange"
                                :class="[
                                    'text-sm font-medium',
                                    match.ratingChange > 0 ? 'text-green-600' : 'text-red-600'
                                ]"
                            >
                                {{ match.ratingChange > 0 ? '+' : '' }}{{ match.ratingChange }}
                            </span>
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
                                    match.result === 'win' ? 'bg-green-100 text-green-700' :
                                    match.result === 'lose' ? 'bg-red-100 text-red-700' :
                                    'bg-gray-100 text-gray-700'
                                ]"
                            >
                                {{ getResultText(match.result) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No matches yet -->
            <div v-if="ratings.length === 0" class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                <p class="text-gray-500">No matches played yet.</p>
            </div>
        </div>
    </div>
</template>
