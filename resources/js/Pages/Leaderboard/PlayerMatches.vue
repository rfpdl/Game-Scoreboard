<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { Game, Match, PlayerRating } from '@/types';
import Badge from '@/Components/atoms/Badge';
import RatingBadge from '@/Components/atoms/RatingBadge';
import Avatar from '@/Components/atoms/Avatar';

interface Player {
    id: number;
    name: string;
    avatar: string | null;
}

interface Props {
    game: Game;
    player: Player;
    rating: PlayerRating | null;
    matches: {
        data: Match[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
}

const props = defineProps<Props>();

const getOpponent = (match: Match) => {
    return match.players.find(p => p.userId !== props.player.id);
};

const getPlayerResult = (match: Match) => {
    const player = match.players.find(p => p.userId === props.player.id);
    return player?.result;
};

const getPlayerRatingChange = (match: Match) => {
    const player = match.players.find(p => p.userId === props.player.id);
    return player?.ratingChange ?? 0;
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <Head :title="`${player.name}'s ${game.name} Matches`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('leaderboard.index', { game: game.slug })" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ player.name }}'s {{ game.name }} Matches
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
                <!-- Player Stats Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <Avatar :src="player.avatar" :name="player.name" size="lg" />
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ player.name }}</h3>
                                <p class="text-sm text-gray-500">{{ game.name }}</p>
                            </div>
                        </div>
                        <div v-if="rating" class="text-right">
                            <RatingBadge :rating="rating.rating" size="lg" />
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div v-if="rating" class="mt-6 grid grid-cols-4 gap-4 border-t pt-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ rating.matchesPlayed }}</p>
                            <p class="text-sm text-gray-500">Matches</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">{{ rating.wins }}</p>
                            <p class="text-sm text-gray-500">Wins</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-600">{{ rating.losses }}</p>
                            <p class="text-sm text-gray-500">Losses</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ rating.winRate }}%</p>
                            <p class="text-sm text-gray-500">Win Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Match History -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Match History</h3>
                    </div>

                    <div v-if="matches.data.length > 0">
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="match in matches.data"
                                :key="match.uuid"
                                class="p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <Badge
                                            :variant="getPlayerResult(match) === 'win' ? 'success' : 'danger'"
                                            size="sm"
                                        >
                                            {{ getPlayerResult(match) === 'win' ? 'WIN' : 'LOSS' }}
                                        </Badge>
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                vs {{ getOpponent(match)?.userName || 'Unknown' }}
                                            </p>
                                            <p v-if="match.name" class="text-sm text-gray-500">
                                                {{ match.name }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ formatDate(match.playedAt) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            :class="[
                                                'text-lg font-semibold',
                                                getPlayerRatingChange(match) >= 0 ? 'text-green-600' : 'text-red-600'
                                            ]"
                                        >
                                            {{ getPlayerRatingChange(match) >= 0 ? '+' : '' }}{{ getPlayerRatingChange(match) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="matches.last_page > 1" class="px-4 py-3 border-t border-gray-200">
                            <nav class="flex items-center justify-center gap-1">
                                <template v-for="link in matches.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="px-3 py-2 text-sm rounded-lg"
                                        :class="link.active
                                            ? 'bg-indigo-500 text-white'
                                            : 'text-gray-700 hover:bg-gray-100'"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-2 text-sm text-gray-400"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>

                    <div v-else class="p-8 text-center text-gray-500">
                        No completed matches yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
